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

        .preview-zoom {
            position: relative;
            overflow: hidden;
        }

        .preview-zoom .preview-overlay {
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        #previewModal {
            display: none;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }

        #previewModal.show {
            display: flex;
            opacity: 1;
            pointer-events: auto;
        }

        #previewModal .preview-modal-content img {
            max-height: 80vh;
        }

        @media (max-width: 767px) {
            #previewModal .preview-modal-content {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
            }

            #previewModal .preview-modal-content img {
                height: 100%;
                object-fit: contain;
            }
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
                                    <script>
                                        console.log('resultado', {!! json_encode($resultado, JSON_UNESCAPED_UNICODE) !!});
                                        console.log('sim', {!! json_encode($sim, JSON_UNESCAPED_UNICODE) !!});
                                    </script>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span class="simulacion-badge badge-info">
                                            🏭 {{ $sim['fabricante'] ?? 'Fabricante desconocido' }}
                                        </span>
                                        <span class="simulacion-badge badge-warning">
                                            📦 {{ $sim['bultos_albaran'] ?? 0 }} bultos
                                        </span>
                                        <span class="simulacion-badge badge-info">
                                            🏷️ {{ count($sim['bultos_simulados'] ?? []) }} coladas
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
                                <div class="flex-shrink-0">
                                    <div class="preview-zoom group relative w-32 h-32 sm:w-36 sm:h-36 rounded-lg shadow-sm border border-gray-200 overflow-hidden cursor-pointer"
                                        data-preview="{{ $resultado['preview'] }}">
                                        <img src="{{ $resultado['preview'] }}" alt="{{ $resultado['nombre_archivo'] }}"
                                            class="w-full h-full object-cover">
                                        <div
                                            class="preview-overlay absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (!$resultado['error'] && isset($sim))
                        <div id="confirmationPrompt-{{ $idx }}" class="px-6 pt-6 pb-4 space-y-4">
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">Datos extraídos</h3>
                                        <p class="text-xs text-gray-500">Revisa la información escaneada del albarán.
                                        </p>
                                    </div>
                                </div>

                                <!-- Vista de datos (modo lectura) -->
                                <div id="viewMode-{{ $idx }}" class="space-y-3">
                                    <!-- Datos generales del albarán -->
                                    <div class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-3 text-sm text-gray-700">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Albarán</span>
                                            <span class="font-semibold extracted-value"
                                                data-field="albaran">{{ $resultado['parsed']['albaran'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Fecha</span>
                                            <span class="font-semibold extracted-value"
                                                data-field="fecha">{{ $resultado['parsed']['fecha'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Pedido cliente</span>
                                            <span class="font-semibold extracted-value"
                                                data-field="pedido_cliente">{{ $resultado['parsed']['pedido_cliente'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Pedido código</span>
                                            <span class="font-semibold extracted-value"
                                                data-field="pedido_codigo">{{ $resultado['parsed']['pedido_codigo'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Peso total (kg)</span>
                                            <span class="font-semibold extracted-value"
                                                data-field="peso_total">{{ isset($resultado['parsed']['peso_total']) ? number_format($resultado['parsed']['peso_total'], 2, ',', '.') : '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Bultos total</span>
                                            <span class="font-semibold extracted-value"
                                                data-field="bultos_total">{{ $resultado['parsed']['bultos_total'] ?? '—' }}</span>
                                        </div>
                                    </div>

                                    <div class="flex justify-end mt-2">
                                        <button type="button"
                                            class="toggle-json-btn inline-flex items-center gap-1 px-3 py-1 text-[11px] font-semibold text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition"
                                            data-result="{{ $idx }}" data-show-label="Ver JSON"
                                            data-hide-label="Ocultar JSON">
                                            Ver JSON
                                        </button>
                                    </div>
                                    <div id="jsonPayload-{{ $idx }}"
                                        class="hidden bg-slate-950 text-slate-100 rounded-lg p-3 text-[11px] leading-tight overflow-auto max-h-64 mt-2 whitespace-pre-wrap">
                                        <pre class="whitespace-pre-wrap break-words text-[11px]">{{ json_encode($resultado['parsed'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>

                                    <!-- Productos -->
                                    @php
                                        $productos = $resultado['parsed']['productos'] ?? [];
                                    @endphp
                                    @if (count($productos) > 0)
                                        <div class="mt-4">
                                            <h4 class="text-xs font-semibold text-gray-700 mb-2">Productos escaneados:
                                            </h4>
                                            <div class="space-y-2">
                                                @foreach ($productos as $prodIdx => $producto)
                                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                                            <div>
                                                                <span class="text-gray-500">Descripción:</span>
                                                                <span
                                                                    class="font-semibold ml-1">{{ $producto['descripcion'] ?? '—' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-500">Diámetro:</span>
                                                                <span
                                                                    class="font-semibold ml-1">{{ $producto['diametro'] ?? '—' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-500">Longitud:</span>
                                                                <span
                                                                    class="font-semibold ml-1">{{ $producto['longitud'] ?? '—' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-500">Calidad:</span>
                                                                <span
                                                                    class="font-semibold ml-1">{{ $producto['calidad'] ?? '—' }}</span>
                                                            </div>
                                                        </div>
                                                        @if (isset($producto['line_items']) && count($producto['line_items']) > 0)
                                                            <div class="mt-2 text-xs">
                                                                <span class="text-gray-500">Coladas:</span>
                                                                <div class="mt-1 flex flex-wrap gap-1">
                                                                    @foreach ($producto['line_items'] as $item)
                                                                        <span
                                                                            class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-700">
                                                                            {{ $item['colada'] ?? '?' }}
                                                                            <span
                                                                                class="ml-1 text-blue-600">({{ $item['bultos'] ?? 1 }})</span>
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
                                    @php
                                        $coladasResumen = collect($productos)
                                            ->flatMap(fn($producto) => $producto['line_items'] ?? [])
                                            ->filter(
                                                fn($item) => isset($item['colada']) ||
                                                    isset($item['bultos']) ||
                                                    isset($item['peso_kg']),
                                            )
                                            ->values();
                                    @endphp
                                    @if ($coladasResumen->count())
                                        <div
                                            class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-xs text-blue-900 space-y-1">
                                            <p class="font-semibold text-sm text-blue-700">Coladas detectadas antes de
                                                continuar:</p>
                                            <p>
                                                <span class="font-semibold">[coladas]:</span>
                                                @foreach ($coladasResumen as $colada)
                                                    ({{ $colada['colada'] ?? '—' }} ({{ $colada['bultos'] ?? 0 }})
                                                    @if(($resultado['parsed']['proveedor'] ?? '') !== 'megasa')
                                                        {{ isset($colada['peso_kg']) ? number_format($colada['peso_kg'], 3, ',', '.') : '—' }}
                                                        kg
                                                    @endif
                                                    )
                                                @endforeach
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Botones de acción -->
                                    <div class="flex gap-3 mt-4 pt-4 border-t border-gray-200">
                                        <button type="button"
                                            class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg font-semibold text-sm hover:bg-emerald-700 transition confirm-scanned"
                                            data-result="{{ $idx }}">
                                            ✓ Continuar con lo escaneado
                                        </button>
                                        <button type="button"
                                            class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg font-semibold text-sm hover:bg-amber-700 transition modify-scanned"
                                            data-result="{{ $idx }}">
                                            ✎ Modificar lo escaneado
                                        </button>
                                    </div>
                                </div>

                                <!-- Vista de edición (modo edición) -->
                                <div id="editMode-{{ $idx }}" class="hidden space-y-4">
                                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                        <p class="text-xs text-amber-800">
                                            <strong>Modo edición:</strong> Modifica, agrega o elimina datos según sea
                                            necesario.
                                        </p>
                                    </div>

                                    <!-- Datos generales editables -->
                                    <div>
                                        <h5 class="text-xs font-semibold text-gray-700 mb-2">Datos generales del
                                            albarán</h5>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Albarán
                                                <input type="text"
                                                    class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    data-field="albaran"
                                                    value="{{ $resultado['parsed']['albaran'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Fecha
                                                <input type="date"
                                                    class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    data-field="fecha"
                                                    value="{{ $resultado['parsed']['fecha'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Pedido cliente
                                                <input type="text"
                                                    class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    data-field="pedido_cliente"
                                                    value="{{ $resultado['parsed']['pedido_cliente'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Pedido código
                                                <input type="text"
                                                    class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    data-field="pedido_codigo"
                                                    value="{{ $resultado['parsed']['pedido_codigo'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Peso total (kg)
                                                <input type="number" step="0.01"
                                                    class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    data-field="peso_total"
                                                    value="{{ $resultado['parsed']['peso_total'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Bultos total
                                                <input type="number"
                                                    class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    data-field="bultos_total"
                                                    value="{{ $resultado['parsed']['bultos_total'] ?? '' }}">
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Productos editables -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <h5 class="text-xs font-semibold text-gray-700">Productos</h5>
                                            <button type="button"
                                                class="add-producto-btn text-xs px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition"
                                                data-result="{{ $idx }}">
                                                + Agregar producto
                                            </button>
                                        </div>
                                        <div id="productosContainer-{{ $idx }}" class="space-y-3">
                                            @foreach ($resultado['parsed']['productos'] ?? [] as $prodIdx => $producto)
                                                <div class="producto-edit-block bg-gray-50 border border-gray-300 rounded-lg p-3"
                                                    data-producto-index="{{ $prodIdx }}">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs font-semibold text-gray-700">Producto
                                                            {{ $prodIdx + 1 }}</span>
                                                        <button type="button"
                                                            class="remove-producto-btn text-xs px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Descripción
                                                            <select
                                                                class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                                                                data-field="descripcion">
                                                                <option value="">Seleccionar tipo</option>
                                                                <option value="CORRUGADO"
                                                                    {{ ($producto['descripcion'] ?? '') === 'CORRUGADO' ? 'selected' : '' }}>
                                                                    Corrugado
                                                                </option>
                                                                <option value="BARRA"
                                                                    {{ ($producto['descripcion'] ?? '') === 'BARRA' ? 'selected' : '' }}>
                                                                    Barra
                                                                </option>
                                                            </select>
                                                        </label>
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Diámetro
                                                            <input type="text"
                                                                class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                                                                data-field="diametro"
                                                                value="{{ $producto['diametro'] ?? '' }}">
                                                        </label>
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Longitud
                                                            <input type="text"
                                                                class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                                                                data-field="longitud"
                                                                value="{{ $producto['longitud'] ?? '' }}">
                                                        </label>
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Calidad
                                                            <input type="text"
                                                                class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                                                                data-field="calidad"
                                                                value="{{ $producto['calidad'] ?? '' }}">
                                                        </label>
                                                    </div>

                                                    <!-- Coladas del producto -->
                                                    <div>
                                                        <div class="flex items-center justify-between mb-1">
                                                            <span
                                                                class="text-xs font-medium text-gray-600">Coladas</span>
                                                            <button type="button"
                                                                class="add-colada-btn text-xs px-2 py-0.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                                                + Agregar colada
                                                            </button>
                                                        </div>
                                                        <div class="coladas-container space-y-1">
                                                            @foreach ($producto['line_items'] ?? [] as $coladaIdx => $colada)
                                                                <div class="colada-edit-row flex gap-2 items-center"
                                                                    data-colada-index="{{ $coladaIdx }}">
                                                                    <input type="text" placeholder="Colada"
                                                                        class="colada-field flex-1 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                                                                        data-field="colada"
                                                                        value="{{ $colada['colada'] ?? '' }}">
                                                                    <input type="number" placeholder="Bultos"
                                                                        class="colada-field w-20 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                                                                        data-field="bultos"
                                                                        value="{{ $colada['bultos'] ?? '' }}">
                                                                    <input type="number" step="0.01"
                                                                        placeholder="Peso (kg)"
                                                                        class="colada-field w-24 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500"
                                                                        data-field="peso_kg"
                                                                        value="{{ $colada['peso_kg'] ?? '' }}">
                                                                    <button type="button"
                                                                        class="remove-colada-btn text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">
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
                                        <button type="button"
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold text-sm hover:bg-blue-700 transition confirm-edit"
                                            data-result="{{ $idx }}">
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
                                                        <th class="px-4 py-2 text-left font-medium">Pedido</th>
                                                        <th class="px-4 py-2 text-left font-medium">Producto</th>
                                                        <th class="px-4 py-2 text-left font-medium">Obra</th>
                                                        <th class="px-4 py-2 text-center font-medium">Pendiente</th>
                                                        <th class="px-4 py-2 text-center font-medium">Estado</th>
                                                        <th class="px-4 py-2 text-center font-medium">Recomendación
                                                        </th>
                                                        <th class="px-4 py-2 text-center font-medium">Fecha pedido</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach ($sim['lineas_pendientes'] as $linea)
                                                        <tr
                                                            class="hover:bg-gray-50 {{ $loop->first ? 'bg-green-50' : '' }}">
                                                            <td class="px-4 py-3 font-medium text-gray-900">
                                                                {{ $linea['pedido_codigo'] }}
                                                                @if ($loop->first)
                                                                    <span
                                                                        class="ml-2 text-xs bg-green-600 text-white px-2 py-0.5 rounded-full">PROPUESTA</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-700">
                                                                {{ $linea['producto'] }}</td>
                                                            <td class="px-4 py-3 text-gray-700">
                                                                {{ $linea['obra'] ?? ($sim['lugar_entrega'] ?? '—') }}
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-center font-semibold text-gray-900">
                                                                {{ number_format($linea['cantidad_pendiente'], 0, ',', '.') }}
                                                                /
                                                                {{ number_format($linea['cantidad'], 0, ',', '.') }} kg
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <span
                                                                    class="text-xs px-2 py-1 rounded-full {{ $linea['estado'] === 'activo' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                                                    {{ ucfirst($linea['estado']) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                @php
                                                                    $scoreColor =
                                                                        $linea['score'] >= 150
                                                                            ? 'bg-emerald-600'
                                                                            : ($linea['score'] >= 50
                                                                                ? 'bg-green-500'
                                                                                : ($linea['score'] >= 0
                                                                                    ? 'bg-yellow-400'
                                                                                    : 'bg-red-500'));
                                                                @endphp
                                                                <div
                                                                    class="flex items-center justify-center group relative">
                                                                    <div
                                                                        class="w-4 h-4 rounded-full {{ $scoreColor }} shadow-sm cursor-help">
                                                                    </div>
                                                                </div>

                                                                @if (count($linea['razones']) > 0 || count($linea['incompatibilidades']) > 0)
                                                                    <div
                                                                        class="hidden absolute z-10 bg-white border border-gray-300 rounded-lg shadow-lg p-3 text-xs max-w-xs text-left right-0 mt-1">
                                                                        @if (count($linea['razones']) > 0)
                                                                            <div class="mb-2">
                                                                                <strong
                                                                                    class="text-green-700">Razones:</strong>
                                                                                <ul
                                                                                    class="list-disc list-inside text-green-600">
                                                                                    @foreach ($linea['razones'] as $razon)
                                                                                        <li>{{ $razon }}</li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            </div>
                                                                        @endif
                                                                        @if (count($linea['incompatibilidades']) > 0)
                                                                            <div>
                                                                                <strong
                                                                                    class="text-red-700">Incompatibilidades:</strong>
                                                                                <ul
                                                                                    class="list-disc list-inside text-red-600">
                                                                                    @foreach ($linea['incompatibilidades'] as $incomp)
                                                                                        <li>{{ $incomp }}</li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-600 text-center">
                                                                {{ $linea['fecha_creacion'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    @if ($sim['linea_propuesta'])
                                        @php
                                            $tipoRec = $sim['linea_propuesta']['tipo_recomendacion'] ?? 'por_score';
                                            $bgColor = $tipoRec === 'exacta' ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200';
                                            $textColor = $tipoRec === 'exacta' ? 'text-green-900' : 'text-blue-900';
                                            $labelColor = $tipoRec === 'exacta' ? 'bg-green-600 text-white' : 'bg-blue-600 text-white';
                                            $labelText = $tipoRec === 'exacta' ? 'COINCIDENCIA EXACTA' : ($tipoRec === 'parcial' ? 'COINCIDENCIA PARCIAL' : 'MEJOR COMPATIBILIDAD');
                                        @endphp
                                        <div class="mt-3 p-4 {{ $bgColor }} border rounded-lg">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <p class="text-sm font-semibold {{ $textColor }}">
                                                            {{ $tipoRec === 'exacta' ? '✓' : '⚠' }} Línea propuesta:
                                                            {{ $sim['linea_propuesta']['pedido_codigo'] }}
                                                        </p>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold {{ $labelColor }}">
                                                            {{ $labelText }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs {{ $textColor }} mt-1">
                                                        <strong>Fabricante:</strong>
                                                        {{ $sim['linea_propuesta']['fabricante'] }}
                                                    </p>
                                                    <p class="text-xs {{ $textColor }}">
                                                        <strong>Producto:</strong>
                                                        {{ $sim['linea_propuesta']['producto'] }} |
                                                        <strong>Obra:</strong> {{ $sim['linea_propuesta']['obra'] }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    @php
                                                        $propScore = $sim['linea_propuesta']['score'];
                                                        $propColor =
                                                            $propScore >= 150
                                                                ? 'bg-emerald-600'
                                                                : ($propScore >= 50
                                                                    ? 'bg-green-500'
                                                                    : ($propScore >= 0
                                                                        ? 'bg-yellow-400'
                                                                        : 'bg-red-500'));
                                                    @endphp
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
                                                    <p class="text-xs font-semibold text-red-700 mb-1">Advertencias:
                                                    </p>
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
                                    <!-- Listado completo de TODOS los pedidos pendientes (MODAL) -->
                                    @if (isset($sim['todas_las_lineas']) && count($sim['todas_las_lineas']) > 0)
                                        <div class="border-t border-gray-200 pt-4">
                                            <button type="button"
                                                onclick="openPendingOrdersModal('{{ $idx }}')"
                                                class="flex items-center justify-between w-full p-3 bg-gray-100 rounded-lg hover:bg-gray-200 transition text-left">
                                                <span class="text-sm font-semibold text-gray-900">
                                                    > Ver todos los pedidos pendientes
                                                    ({{ count($sim['todas_las_lineas']) }})
                                                </span>
                                            </button>

                                            <!-- Selected Order Display -->
                                            <div id="selectedOrderContainer-{{ $idx }}"
                                                class="hidden mt-3 p-4 bg-blue-50 border border-blue-200 rounded-lg relative">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <p class="text-sm font-semibold text-blue-900">
                                                            ✓ Pedido seleccionado manualmente: <span
                                                                id="selectedOrderCode-{{ $idx }}"></span>
                                                        </p>
                                                        <p class="text-xs text-blue-700 mt-1"
                                                            id="selectedOrderDetails-{{ $idx }}">
                                                            <!-- Details populated by JS -->
                                                        </p>
                                                    </div>
                                                    <button type="button"
                                                        onclick="resetToRecommended('{{ $idx }}')"
                                                        class="text-xs text-blue-600 underline hover:text-blue-800">
                                                        Restaurar recomendado
                                                    </button>
                                                </div>
                                                <div class="mt-3">
                                                    <!-- Enunciado dinámico -->
                                                    <div id="changeStatement-{{ $idx }}"
                                                        class="mb-2 p-2 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-900">
                                                        <strong>Selección manual:</strong>
                                                        <span id="statementText-{{ $idx }}">Has cambiado la recomendación del sistema.</span>
                                                    </div>

                                                    <label class="block text-xs font-medium text-blue-800 mb-1">
                                                        Indica el motivo (opcional - ayudará al sistema a aprender):
                                                    </label>
                                                    <input type="text" id="changeReason-{{ $idx }}"
                                                        class="w-full text-sm rounded-md border-blue-300 focus:border-blue-500 focus:ring-blue-500"
                                                        placeholder="Ej: Mejor calidad, entrega más urgente, etc.">
                                                </div>
                                            </div>

                                            <!-- Modal Structure -->
                                            @php
                                                $recommendedId = $sim['linea_propuesta']['id'] ?? null;
                                                $recommendedCode = $sim['linea_propuesta']['pedido_codigo'] ?? null;
                                            @endphp
                                            <div id="pendingOrdersModal-{{ $idx }}"
                                                class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title"
                                                role="dialog" aria-modal="true"
                                                @if ($recommendedId) data-recommended-id="{{ $recommendedId }}" @endif
                                                @if ($recommendedCode) data-recommended-code="{{ $recommendedCode }}" @endif>
                                                <!-- Backdrop -->
                                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                                    onclick="closePendingOrdersModal('{{ $idx }}')"></div>

                                                <div class="fixed inset-0 z-10 overflow-y-auto">
                                                    <div
                                                        class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                                        <div
                                                            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-7xl">
                                                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                                                <div class="sm:flex sm:items-start">
                                                                    <div
                                                                        class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                                                        <h3 class="text-lg font-semibold leading-6 text-gray-900"
                                                                            id="modal-title">
                                                                            Pedidos Pendientes
                                                                        </h3>
                                                                        <div class="mt-4 max-h-[60vh] overflow-y-auto">
                                                                            <table class="min-w-full text-sm">
                                                                                <thead
                                                                                    class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                                                                                    <tr>
                                                                                        <th
                                                                                            class="px-4 py-2 text-center font-medium">
                                                                                            Pedido</th>
                                                                                        <th
                                                                                            class="px-4 py-2 text-left font-medium">
                                                                                            Fabricante</th>
                                                                                        <th
                                                                                            class="px-4 py-2 text-left font-medium">
                                                                                            Producto</th>
                                                                                        <th
                                                                                            class="px-4 py-2 text-left font-medium">
                                                                                            Obra</th>
                                                                                        <th
                                                                                            class="px-4 py-2 text-center font-medium">
                                                                                            Pendiente</th>
                                                                                        <th
                                                                                            class="px-4 py-2 text-center font-medium">
                                                                                            Recomendación</th>
                                                                                        <th
                                                                                            class="px-4 py-2 text-center font-medium">
                                                                                            Estado</th>
                                                                                        <th
                                                                                            class="px-4 py-2 text-center font-medium">
                                                                                            Acción</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody
                                                                                    class="divide-y divide-gray-200">
                                                                                    @foreach ($sim['todas_las_lineas'] as $linea)
                                                                                        <tr
                                                                                            class="hover:bg-gray-50 {{ $linea['coincide_diametro'] ? 'bg-green-50' : '' }}">
                                                                                            <td
                                                                                                class="px-4 py-3 font-medium text-center text-gray-900">
                                                                                                {{ $linea['pedido_codigo'] }}
                                                                                                @if (isset($sim['linea_propuesta']) && $linea['id'] == $sim['linea_propuesta']['id'])
                                                                                                    <span
                                                                                                        class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Recomendado</span>
                                                                                                @endif
                                                                                            </td>
                                                                                            <td
                                                                                                class="px-4 py-3 text-gray-600 text-xs">
                                                                                                {{ $linea['fabricante'] }}
                                                                                            </td>
                                                                                            <td
                                                                                                class="px-4 py-3 text-gray-700">
                                                                                                @if ($linea['coincide_diametro'])
                                                                                                    <span
                                                                                                        class="ml-1 text-xs text-green-600">✓</span>
                                                                                                @endif
                                                                                                {{ $linea['producto'] }}
                                                                                            </td>
                                                                                            <td
                                                                                                class="px-4 py-3 text-gray-700">
                                                                                                {{ $linea['obra'] }}
                                                                                            </td>
                                                                                            <td
                                                                                                class="px-4 py-3 text-center font-semibold text-gray-900">
                                                                                                {{ number_format($linea['cantidad_pendiente'], 0, ',', '.') }}
                                                                                                /
                                                                                                {{ number_format($linea['cantidad'], 0, ',', '.') }}
                                                                                                kg
                                                                                            </td>
                                                                                            <td
                                                                                                class="px-4 py-3 text-center">
                                                                                                @php
                                                                                                    $scoreColor =
                                                                                                        $linea[
                                                                                                            'score'
                                                                                                        ] >= 150
                                                                                                            ? 'bg-emerald-600'
                                                                                                            : ($linea[
                                                                                                                'score'
                                                                                                            ] >= 50
                                                                                                                ? 'bg-green-500'
                                                                                                                : ($linea[
                                                                                                                    'score'
                                                                                                                ] >= 0
                                                                                                                    ? 'bg-yellow-400'
                                                                                                                    : 'bg-red-500'));
                                                                                                @endphp
                                                                                                <div
                                                                                                    class="flex items-center justify-center group relative">
                                                                                                    <div class="w-4 h-4 rounded-full {{ $scoreColor }} shadow-sm cursor-help"
                                                                                                        title="Score: {{ $linea['score'] }}">
                                                                                                    </div>
                                                                                                </div>
                                                                                            </td>
                                                                                            <td class="px-4 py-3">
                                                                                                <span
                                                                                                    class="text-xs px-2 py-1 rounded-full {{ $linea['estado'] === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700' }}">
                                                                                                    {{ ucfirst($linea['estado']) }}
                                                                                                </span>
                                                                                            </td>
                                                                                            <td
                                                                                                class="px-4 py-3 text-center">
                                                                                                @php
                                                                                                    $isProposed =
                                                                                                        isset(
                                                                                                            $sim[
                                                                                                                'linea_propuesta'
                                                                                                            ],
                                                                                                        ) &&
                                                                                                        $linea['id'] ==
                                                                                                            $sim[
                                                                                                                'linea_propuesta'
                                                                                                            ]['id'];
                                                                                                @endphp
                                                                                                <button type="button"
                                                                                                    id="btn-select-{{ $idx }}-{{ $linea['id'] }}"
                                                                                                    class="selection-btn-{{ $idx }} text-xs px-3 py-1 rounded transition {{ $isProposed ? 'bg-green-600 text-white cursor-default' : 'bg-blue-600 text-white hover:bg-blue-700' }}"
                                                                                                    {{ $isProposed ? 'disabled' : '' }}
                                                                                                    onclick="seleccionarLineaManual({{ json_encode($linea) }}, '{{ $idx }}')">
                                                                                                    {{ $isProposed ? 'Seleccionado' : 'Seleccionar' }}
                                                                                                </button>
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                                <button type="button"
                                                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                                                    onclick="closePendingOrdersModal('{{ $idx }}')">
                                                                    Cerrar
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Simulación: Productos a Crear -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 mb-3">
                                    📦 Coladas a recepcionar
                                </h3>
                                <p class="text-xs text-gray-600 mb-2">
                                    Selecciona las coladas que deseas recepcionar ahora. Puedes desmarcar las que NO
                                    quieras procesar en este momento.
                                </p>
                                @if (count($sim['bultos_simulados']) > 0)
                                    <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-100 text-gray-700">
                                                    <tr>
                                                        <th class="px-4 py-2 text-center font-medium">
                                                            <input type="checkbox" id="checkAll-{{ $idx }}"
                                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                                checked>
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
                                                                    data-peso="{{ $bulto['peso_kg'] ?? 0 }}" checked>
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-gray-700 font-mono font-semibold">
                                                                {{ $bulto['colada'] }}
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-center font-semibold text-gray-900">
                                                                {{ $bulto['bultos'] ?? 1 }}
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-right font-semibold text-gray-900">
                                                                {{ $bulto['peso_kg'] ? number_format($bulto['peso_kg'], 0, ',', '.') : '—' }}
                                                                kg
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                                                    <tr>
                                                        <td colspan="2"
                                                            class="px-4 py-3 text-sm font-semibold text-gray-700 text-right">
                                                            TOTALES SELECCIONADOS:
                                                        </td>
                                                        <td class="px-4 py-3 text-center text-sm font-bold text-blue-700"
                                                            id="totalBultos-{{ $idx }}">
                                                            {{ collect($sim['bultos_simulados'])->sum('bultos') }}
                                                        </td>
                                                        <td class="px-4 py-3 text-right text-sm font-bold text-blue-700"
                                                            id="totalPeso-{{ $idx }}">
                                                            {{ number_format(collect($sim['bultos_simulados'])->sum('peso_kg'), 0, ',', '.') }}
                                                            kg
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
                            @if ($sim['linea_propuesta'])
                                <div id="estadoFinalSection-{{ $idx }}"
                                    data-cantidad-inicial="{{ $sim['linea_propuesta']['cantidad_recepcionada'] }}"
                                    data-cantidad-total="{{ $sim['linea_propuesta']['cantidad'] }}">
                                    <h3
                                        class="text-base font-semibold text-gray-900 mb-3 flex items-center justify-between">
                                        <span class="flex items-center gap-2">
                                            📊 Estado final simulado de la línea
                                            <span id="applyingLabel-{{ $idx }}"
                                                class="text-xs font-normal px-2 py-1 rounded-full bg-green-100 text-green-700">Recomendado</span>
                                        </span>
                                    </h3>
                                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Cantidad pendiente
                                                    actual
                                                </p>
                                                <p class="text-2xl font-bold text-indigo-900"
                                                    id="cantidadPendienteActual-{{ $idx }}">
                                                    {{ number_format($sim['linea_propuesta']['cantidad_pendiente'], 0, ',', '.') }}
                                                    kg
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Kg a recepcionar
                                                </p>
                                                <p class="text-2xl font-bold text-indigo-900"
                                                    id="kgRecepcionar-{{ $idx }}">
                                                    {{ number_format(collect($sim['bultos_simulados'])->sum('peso_kg'), 0, ',', '.') }}
                                                    kg
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Kg pendientes después
                                                </p>
                                                <p class="text-2xl font-bold text-indigo-900"
                                                    id="kgPendientesDespues-{{ $idx }}">
                                                    {{ number_format($sim['linea_propuesta']['cantidad_pendiente'] - collect($sim['bultos_simulados'])->sum('peso_kg'), 0, ',', '.') }}
                                                    kg
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Nuevo estado</p>
                                                <p id="nuevoEstado-{{ $idx }}"
                                                    class="text-lg font-bold {{ $sim['linea_propuesta']['cantidad_recepcionada'] + collect($sim['bultos_simulados'])->sum('peso_kg') >= $sim['linea_propuesta']['cantidad'] ? 'text-green-600' : 'text-blue-600' }}">
                                                    {{ $sim['linea_propuesta']['cantidad_recepcionada'] + collect($sim['bultos_simulados'])->sum('peso_kg') >= $sim['linea_propuesta']['cantidad'] ? 'Completado' : 'Parcial' }}
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
                                                    ({{ $linea['cantidad_pendiente'] }} kg pendientes - Score:
                                                    {{ $linea['score'] }})
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

        document.querySelectorAll('.toggle-json-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = btn.dataset.result;
                const payload = document.getElementById(`jsonPayload-${idx}`);
                if (!payload) {
                    return;
                }

                const isHidden = payload.classList.toggle('hidden');
                const showLabel = btn.dataset.showLabel || 'Ver JSON';
                const hideLabel = btn.dataset.hideLabel || 'Ocultar JSON';
                btn.textContent = isHidden ? showLabel : hideLabel;
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
                                <select class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="descripcion">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="CORRUGADO">Corrugado</option>
                                    <option value="BARRA">Barra</option>
                                </select>
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
                    const container = e.target.closest('.producto-edit-block').querySelector(
                        '.coladas-container');
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

        // Función para actualizar totales de coladas seleccionadas y estado final
        function updateColadaTotals(idx) {
            const checkboxes = document.querySelectorAll(`.colada-checkbox-${idx}:checked`);
            let totalBultos = 0;
            let totalPeso = 0;

            checkboxes.forEach(cb => {
                totalBultos += parseInt(cb.dataset.bultos) || 0;
                totalPeso += parseFloat(cb.dataset.peso) || 0;
            });

            // Actualizar totales de bultos y peso
            document.getElementById(`totalBultos-${idx}`).textContent = totalBultos;
            document.getElementById(`totalPeso-${idx}`).textContent = new Intl.NumberFormat('es-ES').format(totalPeso) +
                ' kg';

            // Actualizar estado final simulado
            const estadoFinalSection = document.getElementById(`estadoFinalSection-${idx}`);
            if (estadoFinalSection) {
                const cantidadInicial = parseFloat(estadoFinalSection.dataset.cantidadInicial) || 0;
                const cantidadTotal = parseFloat(estadoFinalSection.dataset.cantidadTotal) || 0;
                const cantidadPendienteActual = cantidadTotal - cantidadInicial;

                // Calcular nuevos valores
                const kgRecepcionar = totalPeso;
                const kgPendientesDespues = cantidadPendienteActual - kgRecepcionar;
                const cantidadRecepcionadaNueva = cantidadInicial + kgRecepcionar;
                const nuevoEstado = cantidadRecepcionadaNueva >= cantidadTotal ? 'Completado' : 'Parcial';

                // Actualizar UI
                document.getElementById(`cantidadPendienteActual-${idx}`).textContent =
                    new Intl.NumberFormat('es-ES').format(cantidadPendienteActual) + ' kg';
                document.getElementById(`kgRecepcionar-${idx}`).textContent =
                    new Intl.NumberFormat('es-ES').format(kgRecepcionar) + ' kg';
                document.getElementById(`kgPendientesDespues-${idx}`).textContent =
                    new Intl.NumberFormat('es-ES').format(kgPendientesDespues) + ' kg';

                const nuevoEstadoElement = document.getElementById(`nuevoEstado-${idx}`);
                nuevoEstadoElement.textContent = nuevoEstado;

                // Actualizar colores según estado
                if (nuevoEstado === 'Completado') {
                    nuevoEstadoElement.className = 'text-lg font-bold text-green-600';
                } else {
                    nuevoEstadoElement.className = 'text-lg font-bold text-blue-600';
                }
            }
        }

        // Añadir listeners a checkboxes "marcar/desmarcar todos"
        document.querySelectorAll('[id^="checkAll-"]').forEach(checkAllBox => {
            checkAllBox.addEventListener('change', function() {
                const idx = this.id.replace('checkAll-', '');
                const checkboxes = document.querySelectorAll(`.colada-checkbox-${idx}`);
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateColadaTotals(idx);
            });
        });

        // Añadir listeners a todos los checkboxes de coladas
        document.querySelectorAll('[class*="colada-checkbox-"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const idx = this.className.match(/colada-checkbox-(\d+)/)[1];
                updateColadaTotals(idx);

                // Actualizar estado del checkbox "marcar todos"
                const allCheckboxes = document.querySelectorAll(`.colada-checkbox-${idx}`);
                const checkedCheckboxes = document.querySelectorAll(`.colada-checkbox-${idx}:checked`);
                const checkAllBox = document.getElementById(`checkAll-${idx}`);
                if (checkAllBox) {
                    checkAllBox.checked = allCheckboxes.length === checkedCheckboxes.length;
                }
            });
        });

        // Función para abrir/cerrar modal
        window.openPendingOrdersModal = function(idx) {
            document.getElementById(`pendingOrdersModal-${idx}`).classList.remove('hidden');
        };

        window.closePendingOrdersModal = function(idx) {
            document.getElementById(`pendingOrdersModal-${idx}`).classList.add('hidden');
        };

        // Variables globales para almacenar el estado original (recomendado) por cada resultado
        // Se inicializan en el DOM o lazy loaded
        const originalRecommendations = {};

        // Función auxiliar para actualizar estado visual de botones
        window.updateSelectionButtons = function(idx, selectedId) {
            document.querySelectorAll(`.selection-btn-${idx}`).forEach(btn => {
                // Reset (blue state)
                btn.disabled = false;
                btn.textContent = 'Seleccionar';
                btn.classList.remove('bg-green-600', 'cursor-default');
                btn.classList.add('bg-blue-600', 'hover:bg-blue-700');

                // Set Selected (green state)
                if (btn.id === `btn-select-${idx}-${selectedId}`) {
                    btn.disabled = true;
                    btn.textContent = 'Seleccionado';
                    btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    btn.classList.add('bg-green-600', 'cursor-default');
                }
            });
        };

        // Función para seleccionar manualmente una línea de pedido
        window.seleccionarLineaManual = function(linea, resultadoIdx) {
            // Verificar si la línea seleccionada es la recomendada
            const modal = document.getElementById(`pendingOrdersModal-${resultadoIdx}`);
            const recommendedId = modal?.getAttribute('data-recommended-id') ?? null;

            // Actualizar botones visualmente
            updateSelectionButtons(resultadoIdx, linea.id);

            // Si se selecciona la recomendada, restaurar y salir
            if (linea.id == recommendedId) {
                resetToRecommended(resultadoIdx);
                // No cerramos el modal aquí para que el usuario vea el cambio a "Seleccionado", 
                // o si prefiere cerrar, puede hacerlo. Pero la lógica original decía "restaurar y salir".
                // El usuario pidió "cambia el boton seleccionar por seleccionado". 
                // Si el usuario elige el recomendado, visualmente se marca.
                // Reset data logic:
                // resetToRecommended(resultadoIdx); 
                // BUT resetToRecommended might hide the manual selection UI which is correct.
                // We should probably allow the user to see it. 
                // Let's stick to closing it if it was the behavior used before, or maybe keep it open?
                // The previous code closed it: closePendingOrdersModal(resultadoIdx);
                // I will keep closing it for usability unless they want to keep exploring.
                // Actually, if I update the button state, maybe I shouldn't close it instantly? 
                // Let's close it as per previous flow to avoid confusion, but updating buttons ensures consistency if reopened.
                closePendingOrdersModal(resultadoIdx);
                return;
            }

            // Guardar estado original si no existe
            const finalSection = document.getElementById(`estadoFinalSection-${resultadoIdx}`);
            if (finalSection && !originalRecommendations[resultadoIdx]) {
                originalRecommendations[resultadoIdx] = {
                    cantidadPendiente: parseFloat(finalSection.dataset.cantidadInicial) || 0,
                    cantidadTotal: parseFloat(finalSection.dataset.cantidadTotal) || 0,
                    pedidoCodigo: '{{-- This would be hard to capture effectively without passing it, but we can assume we resort to text --}}'
                };
            }

            // Actualizar vista de selección manual (solo si existen los elementos)
            const container = document.getElementById(`selectedOrderContainer-${resultadoIdx}`);
            const codeSpan = document.getElementById(`selectedOrderCode-${resultadoIdx}`);
            const detailsP = document.getElementById(`selectedOrderDetails-${resultadoIdx}`);

            if (container) {
                container.classList.remove('hidden');
            }
            if (codeSpan) {
                codeSpan.textContent = linea.pedido_codigo;
            }
            if (detailsP) {
                detailsP.innerHTML =
                    `<strong>Producto:</strong> ${linea.producto} | <strong>Obra:</strong> ${linea.obra} | <strong>Pendiente:</strong> ${new Intl.NumberFormat('es-ES').format(linea.cantidad_pendiente)} kg`;
            }

            // Actualizar Label (solo si existe)
            const applyingLabel = document.getElementById(`applyingLabel-${resultadoIdx}`);
            if (applyingLabel) {
                applyingLabel.textContent = "Selección Manual";
                applyingLabel.className = "text-xs font-normal px-2 py-1 rounded-full bg-blue-100 text-blue-700";
            }

            // Actualizar enunciado del cambio (solo si existe)
            const statementText = document.getElementById(`statementText-${resultadoIdx}`);
            if (statementText && recommendedId) {
                // Obtener código del pedido recomendado desde el modal
                const recommendedCode = modal?.getAttribute('data-recommended-code') || 'el recomendado';
                const selectedCode = linea.pedido_codigo || 'otro pedido';

                // Actualizar enunciado
                statementText.innerHTML = `El sistema recomendó <strong>${recommendedCode}</strong> pero preferiste <strong>${selectedCode}</strong>.`;
            }

            // Enfocar el input para que escriba el motivo
            const changeReasonInput = document.getElementById(`changeReason-${resultadoIdx}`);
            if (changeReasonInput) {
                changeReasonInput.focus();
            }

            // Actualizar datos de simulación final
            if (finalSection) {
                finalSection.dataset.cantidadInicial = linea.cantidad_recepcionada || (linea.cantidad - linea
                    .cantidad_pendiente);
                finalSection.dataset.cantidadTotal = linea.cantidad;

                updateColadaTotals(resultadoIdx);
            }

            // Cerrar modal
            closePendingOrdersModal(resultadoIdx);
        };

        window.resetToRecommended = function(idx) {
            // Restaurar botones visualmente al recomendado
            const modal = document.getElementById(`pendingOrdersModal-${idx}`);
            const recommendedId = modal.getAttribute('data-recommended-id');
            updateSelectionButtons(idx, recommendedId);

            // Ocultar contenedor de selección manual (solo si existe)
            const selectedContainer = document.getElementById(`selectedOrderContainer-${idx}`);
            if (selectedContainer) {
                selectedContainer.classList.add('hidden');
            }
            const changeReasonInput = document.getElementById(`changeReason-${idx}`);
            if (changeReasonInput) {
                changeReasonInput.value = ''; // Limpiar razón
            }

            // Restaurar Label (solo si existe)
            const applyingLabel = document.getElementById(`applyingLabel-${idx}`);
            if (applyingLabel) {
                applyingLabel.textContent = "Recomendado";
                applyingLabel.className = "text-xs font-normal px-2 py-1 rounded-full bg-green-100 text-green-700";
            }

            // Restaurar valores originales en Final Section
            if (originalRecommendations[idx]) {
                const finalSection = document.getElementById(`estadoFinalSection-${idx}`);
                // NOTE: We need to restore the ORIGINAL PHP values. 
                // Since I cannot easily read them back from JS variable if I didn't store them all, 
                // I should have stored them in data-attributes of the reset button or similar.
                // Or I can just reload them from the DOM if I hadn't overwritten them? 
                // No, I overwrote the datasets.

                // Better approach: Store original values in data attributes of the container ON LOAD (or first time)
                // Actually, I can use a separate attribute 'data-original-cantidad-inicial' 
            }

            // To Fix Reset Logic:
            // I will rely on `originalRecommendations` which I populated on first selection.
            // However, that only populates IF I select something.
            // Wait, `originalRecommendations[idx]` has what I need?

            const finalSection = document.getElementById(`estadoFinalSection-${idx}`);
            // Let's grab the originals from a backup attribute I should add in the view or handle here.

            // Quick fix: I'll read the 'original' values from attributes I will add to the HTML in a moment.
            // OR I can just reload the page... No, that's bad.

            if (finalSection && finalSection.dataset.originalCantidadInicial !== undefined) {
                finalSection.dataset.cantidadInicial = finalSection.dataset.originalCantidadInicial;
                finalSection.dataset.cantidadTotal = finalSection.dataset.originalCantidadTotal;
                updateColadaTotals(idx);
            }
        };

        // Patch updateColadaTotals to store original values if not present
        const originalUpdateColadaTotals = updateColadaTotals;
        updateColadaTotals = function(idx) {
            const finalSection = document.getElementById(`estadoFinalSection-${idx}`);
            if (finalSection && finalSection.dataset.originalCantidadInicial === undefined) {
                finalSection.dataset.originalCantidadInicial = finalSection.dataset.cantidadInicial;
                finalSection.dataset.originalCantidadTotal = finalSection.dataset.cantidadTotal;
            }
            originalUpdateColadaTotals(idx);
        }

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
    <div id="previewModal"
        class="fixed inset-0 z-50 items-center justify-center bg-black/70 p-4 opacity-0 pointer-events-none transition-opacity duration-200">
        <div class="preview-modal-content relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden">
            <button id="previewModalClose"
                class="absolute top-3 right-3 z-20 text-white bg-black/40 hover:bg-black/60 rounded-full p-1">
                <span class="sr-only">Cerrar vista previa</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img id="previewModalImg" class="w-full h-auto object-contain" alt="Previsualización ampliada">
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('previewModal');
            const modalImg = document.getElementById('previewModalImg');
            const closeBtn = document.getElementById('previewModalClose');
            const openPreview = (src) => {
                if (!src) return;
                modalImg.src = src;
                modal.classList.add('show');
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                document.body.classList.add('overflow-hidden');
            };
            const closePreview = () => {
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                modalImg.src = '';
                modal.classList.remove('show');
                document.body.classList.remove('overflow-hidden');
            };
            closeBtn?.addEventListener('click', closePreview);
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closePreview();
                }
            });
            document.querySelectorAll('.preview-zoom').forEach((el) => {
                el.addEventListener('click', () => {
                    openPreview(el.dataset.preview);
                });
            });
        });
    </script>
</x-app-layout>
