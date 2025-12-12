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

        .processing-circle {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.9);
            filter: blur(2px);
            animation: blurPulse 1.6s ease-in-out infinite;
        }

        @keyframes blurPulse {

            0%,
            100% {
                filter: blur(2px);
            }

            50% {
                filter: blur(0);
            }
        }

        .processing-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.9), rgba(59, 130, 246, 0.9));
            color: white;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .processing-overlay.hidden {
            display: none !important;
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

        /* ========================================== */
        /* CSS PARA VISTA MÓVIL PASO A PASO */
        /* ========================================== */

        /* Container de pasos móvil */
        #stepWrapper {
            display: flex;
            width: 500%;
            /* 5 vistas x 100% */
            transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Cada vista ocupa 20% del wrapper (100% del viewport) */
        #stepWrapper>div {
            width: 20%;
            /* Relativo al wrapper de 500% */
            flex-shrink: 0;
        }

        /* Animaciones para botones móviles */
        .mobile-btn-slide-in {
            animation: slideInFromBottom 300ms ease-out;
        }

        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Overlay para modal en móvil */
        .mobile-modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Modal simple para edición (Vista 2) */
        .mobile-edit-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: flex-end;
        }

        .mobile-edit-modal.show {
            display: flex;
        }

        .mobile-edit-modal-content {
            background: white;
            border-radius: 1.5rem 1.5rem 0 0;
            max-height: 90vh;
            width: 100%;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mobile-edit-modal.show .mobile-edit-modal-content {
            transform: translateY(0);
        }

        /* Prevenir zoom en iOS */
        input,
        select,
        textarea,
        button {
            font-size: 16px;
        }
    </style>
    <x-slot name="title">Revisión asistida de albaranes</x-slot>

    <!-- Vista Desktop (≥768px) -->
    <div id="desktopView" class="hidden md:block">
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
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white text-sm font-semibold shadow hover:bg-indigo-700 cursor-pointer transition">
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
                                    <h2 class="text-lg font-semibold text-gray-900">{{ $resultado['nombre_archivo'] }}
                                    </h2>
                                    @php
                                        $sim = $resultado['simulacion'] ?? [];
                                        if (!isset($sim['linea_propuesta'])) {
                                            $sim['linea_propuesta'] = [];
                                        }
                                        $parsed = $resultado['parsed'] ?? [];
                                        $tipoCompraRaw = $parsed['tipo_compra'] ?? null;
                                        $tipoCompra = $tipoCompraRaw ? mb_strtolower($tipoCompraRaw) : null;
                                        $tipoCompraValid = in_array($tipoCompra, ['directo', 'proveedor']);
                                        $proveedorTexto = $parsed['proveedor_texto'] ?? null;
                                        $distribuidorRecomendado = $parsed['distribuidor_recomendado'] ?? null;
                                        $distribuidoresList = $distribuidores ?? [];
                                    @endphp
                                    @if ($resultado['error'])
                                        <p class="text-sm text-red-600 mt-1">{{ $resultado['error'] }}</p>
                                    @else
                                        <script>
                                            // console.log('resultado', {!! json_encode($resultado, JSON_UNESCAPED_UNICODE) !!});
                                            // console.log('sim', {!! json_encode($sim, JSON_UNESCAPED_UNICODE) !!});
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
                                            <img src="{{ $resultado['preview'] }}"
                                                alt="{{ $resultado['nombre_archivo'] }}"
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
                            @php
                                $lineaInfoText = $sim['linea_propuesta']
                                    ? trim(
                                        ($sim['linea_propuesta']['pedido_codigo']
                                            ? "Pedido {$sim['linea_propuesta']['pedido_codigo']}"
                                            : 'Línea propuesta') .
                                            ' • ' .
                                            ($sim['linea_propuesta']['producto'] ?? '') .
                                            ' ' .
                                            ($sim['linea_propuesta']['diametro']
                                                ? "Ø{$sim['linea_propuesta']['diametro']}"
                                                : '') .
                                            ($sim['linea_propuesta']['cantidad']
                                                ? ' • ' .
                                                    number_format($sim['linea_propuesta']['cantidad'], 0, ',', '.') .
                                                    ' kg'
                                                : ''),
                                    )
                                    : 'Línea propuesta sin identificadores';
                                $lineaInfoAttr = e($lineaInfoText);
                            @endphp
                            <div class="mt-3 text-xs text-gray-600" id="linea-info-{{ $idx }}"
                                data-linea-info="{{ $lineaInfoAttr }}">
                                {{ $lineaInfoText }}
                            </div>
                        </div>

                        @if (!$resultado['error'] && isset($sim))
                            <div id="confirmationPrompt-{{ $idx }}" class="px-6 pt-6 pb-4 space-y-4">
                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Datos extraídos</h3>
                                            <p class="text-xs text-gray-500">Revisa la información escaneada del
                                                albarán.
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
                                            <div class="flex flex-col">
                                                <span class="text-xs text-gray-500">Tipo de compra</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-semibold extracted-value"
                                                        data-field="tipo_compra">{{ $tipoCompra ? ucfirst($tipoCompra) : '—' }}</span>
                                                    @if ($tipoCompra === 'directo')
                                                        <span class="text-xs text-gray-500">(Hierros Paco Reyes)</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($tipoCompra !== 'directo')
                                                <div class="flex flex-col md:col-span-2">
                                                    <span class="text-xs text-gray-500">Proveedor detectado</span>
                                                    <span class="font-semibold extracted-value flex items-center gap-1"
                                                        data-field="proveedor_texto">
                                                        {{ $proveedorTexto ?? '—' }}
                                                        <span class="text-xs text-gray-500">
                                                            ({{ $distribuidorRecomendado ?? 'Sin sugerencia' }})
                                                        </span>
                                                    </span>
                                                </div>
                                            @endif
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
                                                <h4 class="text-xs font-semibold text-gray-700 mb-2">Productos
                                                    escaneados:
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
                                                <strong>Modo edición:</strong> Modifica, agrega o elimina datos según
                                                sea
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
                                                <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                    Tipo de compra
                                                    <select
                                                        class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        data-field="tipo_compra">
                                                        <option value="" {{ $tipoCompra ? '' : 'selected' }}>Sin
                                                            clasificar</option>
                                                        <option value="directo"
                                                            {{ $tipoCompra === 'directo' ? 'selected' : '' }}>Directo
                                                        </option>
                                                        <option value="proveedor"
                                                            {{ $tipoCompra === 'proveedor' ? 'selected' : '' }}>
                                                            Proveedor
                                                        </option>
                                                    </select>
                                                </label>
                                                @if ($tipoCompra !== 'directo')
                                                    <label
                                                        class="text-xs text-gray-700 font-medium flex flex-col gap-1 md:col-span-2">
                                                        Texto proveedor detectado
                                                        <input type="text"
                                                            class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            data-field="proveedor_texto"
                                                            value="{{ $proveedorTexto ?? '' }}">
                                                    </label>
                                                    <label
                                                        class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                        Proveedor sugerido
                                                        <select
                                                            class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            data-field="distribuidor_recomendado">
                                                            <option value="">Sin seleccionar</option>
                                                            @foreach ($distribuidoresList as $distribuidor)
                                                                <option value="{{ $distribuidor }}"
                                                                    {{ $distribuidorRecomendado === $distribuidor ? 'selected' : '' }}>
                                                                    {{ $distribuidor }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                @endif
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
                                                                    <option value="ENCARRETADO"
                                                                        {{ ($producto['descripcion'] ?? '') === 'ENCARRETADO' ? 'selected' : '' }}>
                                                                        Encarretado
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

                            <div id="simulationSection-{{ $idx }}" class="p-6 space-y-4 hidden">
                                <!-- Simulación: Líneas Pendientes -->
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 mb-3">
                                        📋 Líneas de pedido pendientes para este proveedor
                                    </h3>
                                    @if ($sim['hay_coincidencias'])
                                        @if ($sim['linea_propuesta'])
                                            @php
                                                $tipoRec = $sim['linea_propuesta']['tipo_recomendacion'] ?? 'por_score';
                                                $bgColor =
                                                    $tipoRec === 'exacta'
                                                        ? 'bg-green-50 border-green-200'
                                                        : 'bg-blue-50 border-blue-200';
                                                $textColor = $tipoRec === 'exacta' ? 'text-green-900' : 'text-blue-900';
                                                $labelColor =
                                                    $tipoRec === 'exacta'
                                                        ? 'bg-green-600 text-white'
                                                        : 'bg-blue-600 text-white';
                                                $labelText =
                                                    $tipoRec === 'exacta'
                                                        ? 'COINCIDENCIA EXACTA'
                                                        : ($tipoRec === 'parcial'
                                                            ? 'COINCIDENCIA PARCIAL'
                                                            : 'MEJOR COMPATIBILIDAD');
                                            @endphp
                                            <div class="mt-3 p-4 {{ $bgColor }} border rounded-lg">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <p class="text-sm font-semibold {{ $textColor }}">
                                                                {{ $tipoRec === 'exacta' ? '✓' : '⚠' }} Línea
                                                                propuesta:
                                                                {{ $sim['linea_propuesta']['pedido_codigo'] }}
                                                            </p>
                                                            <span
                                                                class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[9px] font-semibold {{ $labelColor }}">
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
                                                            <strong>Obra:</strong>
                                                            {{ $sim['linea_propuesta']['obra'] }}
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
                                                        <p class="text-xs font-semibold text-green-800 mb-1">Razones:
                                                        </p>
                                                        <ul class="text-xs text-green-700 space-y-1 pl-4">
                                                            @foreach ($sim['linea_propuesta']['razones'] as $razon)
                                                                <li>{{ $razon }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                                @if (count($sim['linea_propuesta']['incompatibilidades']) > 0)
                                                    <div class="mt-2 pt-2 border-t border-green-200">
                                                        <p class="text-xs font-semibold text-red-700 mb-1">
                                                            Advertencias:
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
                                    <div class="pb-4 border-b border-gray-200">
                                        <!-- Listado completo de TODOS los pedidos pendientes (MODAL) -->
                                        @if (isset($sim['todas_las_lineas']) && count($sim['todas_las_lineas']) > 0)
                                            <div class="">
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
                                                            <span id="statementText-{{ $idx }}">Has cambiado
                                                                la
                                                                recomendación del sistema.</span>
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
                                                        onclick="closePendingOrdersModal('{{ $idx }}')">
                                                    </div>

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
                                                                            <div
                                                                                class="mt-4 max-h-[60vh] overflow-y-auto">
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
                                                                                                                    ] >=
                                                                                                                    0
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
                                                                                                            $linea[
                                                                                                                'id'
                                                                                                            ] ==
                                                                                                                $sim[
                                                                                                                    'linea_propuesta'
                                                                                                                ]['id'];
                                                                                                    @endphp
                                                                                                    <button
                                                                                                        type="button"
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
                                                                <input type="checkbox"
                                                                    id="checkAll-{{ $idx }}"
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
                                                                        data-peso="{{ $bulto['peso_kg'] ?? 0 }}"
                                                                        checked>
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
                                                📊 Estado final de la línea
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
                                                    <p class="text-xs text-indigo-600 font-medium">Kg pendientes
                                                        después
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
                                        <div class="mt-4 flex justify-end">
                                            <button type="button"
                                                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-tr from-indigo-600 to-indigo-700 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 shadow transition"
                                                onclick="activarLineaSeleccionada({{ $idx }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 4v16m8-8H4" />
                                                </svg>
                                                Activar línea seleccionada
                                            </button>
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

                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div> <!-- Fin desktopView -->

    <!-- ========================================== -->
    <!-- VISTA MÓVIL PASO A PASO (<768px) -->
    <!-- ========================================== -->
    <div id="mobileStepContainer" class="block md:hidden relative overflow-hidden h-calc(100vh - 56px) bg-gray-100">
        <!-- Header fijo superior -->
        <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Botón retroceder -->
                <button id="mobile-back-btn" type="button"
                    class="hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Título del paso actual -->
                <h2 id="mobile-step-title" class="text-lg font-semibold text-gray-900">
                    Subir Albarán
                </h2>

                <!-- Indicador de paso (1/5, 2/5, etc) -->
                <span class="text-sm text-gray-500">
                    <span id="mobile-current-step">1</span>/5
                </span>
            </div>

            <!-- Barra de progreso -->
            <div class="h-1 bg-gray-200">
                <div id="mobile-progress-bar"
                    class="h-full bg-gradient-to-tr from-indigo-600 to-indigo-700 transition-all duration-300"
                    style="width: 20%"></div>
            </div>
        </div>

        <!-- Step wrapper con 5 vistas -->
        <div id="stepWrapper"
            class="flex transition-transform duration-300 ease-in-out h-[calc(100vh-162px)] overflow-y-auto"
            style="width: 500%; transform: translateX(0%)">

            <!-- ===== VISTA 1: SUBIR FOTO ===== -->
            <div id="step-1" class="w-full flex-shrink-0 px-4 py-6">
                <div class="max-w-md mx-auto space-y-3">
                    <h3 class="text-xl font-semibold text-gray-900">Subir Albarán</h3>
                    <p class="text-sm text-gray-600">Selecciona el proveedor y sube una foto del albarán</p>

                    <!-- Formulario móvil -->
                    <form id="ocrForm-mobile" class="space-y-4">
                        @csrf
                        <!-- Selector de proveedor -->
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Proveedor</span>
                            <select name="proveedor" id="proveedor-mobile" required
                                class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                                <option value="">Selecciona proveedor</option>
                                <option value="siderurgica">Siderúrgica Sevillana (SISE)</option>
                                <option value="megasa">Megasa</option>
                                <option value="balboa">Balboa</option>
                                <option value="otro">Otro / No listado</option>
                            </select>
                        </label>

                        <!-- Input de archivo -->
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Foto del albarán</span>
                            <input type="file" name="imagenes[]" id="imagenes-mobile"
                                accept="image/*,application/pdf" required
                                class="mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </label>

                        <!-- Botón procesar -->
                        <button type="button" id="processBtn-mobile" onclick="procesarAlbaranMobile()"
                            class="relative w-full px-4 py-3 bg-gray-300 text-gray-500 rounded-lg font-semibold text-lg shadow-lg disabled:opacity-60 disabled:cursor-not-allowed transition overflow-hidden flex items-center justify-center"
                            disabled>
                            <span id="processBtnLabel-mobile">Procesar albarán</span>
                            <span id="processing-mobile" class="processing-overlay hidden">
                                <span class="processing-circle"></span>
                                <span>Procesando albarán</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- ===== VISTA 2: CONFIRMAR DATOS ===== -->
            <div id="step-2" class="w-full flex-shrink-0 px-4 py-6">
                <div class="max-w-md mx-auto space-y-3">
                    <h3 class="text-xl font-semibold text-gray-900">Confirmar Datos</h3>

                    <!-- Preview de imagen -->
                    <div id="mobile-preview-container"
                        class="flex hidden items-center justify-start gap-4 max-h-16 bg-gradient-to-tr from-indigo-600 to-indigo-700 rounded-lg p-4 cursor-pointer shadow-md">
                        <div class="overflow-hidden w-16 h-12 rounded-lg border-2 border-white">
                            <img id="mobile-preview-img" src="" alt="Preview"
                                class="w-full h-full object-cover">
                        </div>
                        <p class="text-md text-white">Toca para ver la imagen</p>
                    </div>

                    <div id="mobile-extra-info"
                        class="grid grid-cols-2 gap-3 bg-white p-4 rounded-lg border border-gray-300/50 text-sm shadow-md">
                        <div>
                            <span class="text-gray-500">Tipo compra</span>
                            <p id="mobile-tipo-compra" class="font-semibold text-gray-900 uppercase">—</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Proveedor</span>
                            <p id="mobile-proveedor" class="font-semibold text-gray-900 uppercase">—</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Material</span>
                            <p id="mobile-descripcion" class="font-semibold text-gray-900 uppercase">—</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Pedido</span>
                            <p id="mobile-pedido-cliente-combo" class="font-semibold text-gray-900">—</p>
                        </div>
                    </div>

                    <!-- Datos extraídos -->
                    <div id="mobile-datos-container"
                        class="bg-white rounded-lg p-4 space-y-3 shadow-md border border-gray-300/50">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-500">Albarán:</span>
                                <span id="mobile-albaran" class="block font-medium text-gray-900">—</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Fecha:</span>
                                <span id="mobile-fecha" class="block font-medium text-gray-900">—</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Peso Total:</span>
                                <span id="mobile-peso-total" class="block font-medium text-gray-900">—</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Bultos:</span>
                                <span id="mobile-bultos-total" class="block font-medium text-gray-900">—</span>
                            </div>
                        </div>
                    </div>

                    <div id="mobile-product-groups-section" class="space-y-3">
                        <div id="mobile-product-groups"
                            class="space-y-3 text-sm text-gray-900 bg-white border border-gray-200/70 rounded-lg p-3 shadow-sm">
                            <h4 class="text-sm font-semibold text-gray-900">Coladas y bultos escaneados</h4>
                            <p class="text-xs text-gray-500">Aún no se han procesado productos</p>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex gap-3">
                        <button type="button" onclick="abrirModalEdicionMobile()"
                            class="flex-1 px-4 py-3 bg-gray-200 text-gray-800 rounded-lg text-sm shadow-md hover:shadow-lg">
                            Editar
                        </button>
                        <button type="button" data-mobile-next
                            class="flex-1 px-4 py-3 bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white rounded-lg text-sm shadow-md hover:shadow-lg">
                            Marcar como revisado
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== VISTA 3: PEDIDO SELECCIONADO ===== -->
            <div id="step-3" class="w-full flex-shrink-0 px-4 py-6">
                <div class="max-w-md mx-auto space-y-3">
                    <h3 class="text-xl font-semibold text-gray-900">Pedido Seleccionado</h3>
                    <p class="text-sm text-gray-600">Verifica el pedido propuesto o selecciona otro</p>

                    <!-- Card del pedido será añadido dinámicamente -->
                    <div id="mobile-pedido-card" class="bg-white rounded-lg p-4">
                        <p class="text-gray-500">Cargando...</p>
                    </div>

                    <!-- Botón para ver otros pedidos -->
                    <button type="button" id="mobile-ver-otros-pedidos"
                        class="w-full px-4 py-3 bg-gray-200 text-gray-800 rounded-lg font-medium">
                        Ver otros pedidos
                    </button>

                    <!-- Navegación -->
                    <button type="button" data-mobile-next
                        class="w-full px-4 py-3 bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white rounded-lg font-medium">
                        Continuar
                    </button>
                </div>
            </div>

            <!-- ===== VISTA 4: COLADAS A RECEPCIONAR ===== -->
            <div id="step-4" class="w-full flex-shrink-0 px-4 py-6">
                <div class="max-w-md mx-auto space-y-3">
                    <h3 class="text-xl font-semibold text-gray-900">Coladas a Recepcionar</h3>
                    <p class="text-sm text-gray-600">Selecciona las coladas que deseas recepcionar</p>

                    <!-- Lista de coladas -->
                    <div id="mobile-coladas-container" class="bg-white rounded-lg divide-y">
                        <p class="p-4 text-gray-500">Cargando...</p>
                    </div>

                    <!-- Estado final simulado -->
                    <div id="mobile-estado-final" class="bg-blue-50 rounded-lg p-4 space-y-2">
                        <h4 class="font-semibold text-gray-900">Estado Final</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-gray-600">Bultos seleccionados:</span>
                                <span id="mobile-bultos-seleccionados" class="block font-bold text-gray-900">0</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Peso seleccionado:</span>
                                <span id="mobile-peso-seleccionado" class="block font-bold text-gray-900">0 kg</span>
                            </div>
                        </div>
                    </div>

                    <!-- Navegación -->
                    <button type="button" data-mobile-next
                        class="w-full px-4 py-3 bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white rounded-lg font-medium">
                        Continuar
                    </button>
                </div>
            </div>

            <!-- ===== VISTA 5: ACTIVACIÓN ===== -->
            <div id="step-5" class="w-full flex-shrink-0 px-4 py-6">
                <div class="max-w-md mx-auto space-y-3">
                    <h3 class="text-xl font-semibold text-gray-900">Confirmar Activación</h3>
                    <p class="text-sm text-gray-600">Revisa el resumen y confirma la activación</p>

                    <!-- Resumen -->
                    <div id="mobile-resumen-container" class="bg-white rounded-lg p-4 space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Datos del Albarán</h4>
                            <div class="text-sm space-y-1">
                                <p><span class="text-gray-600">Albarán:</span> <span
                                        id="mobile-resumen-albaran">—</span></p>
                                <p><span class="text-gray-600">Pedido:</span> <span
                                        id="mobile-resumen-pedido">—</span></p>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Coladas Seleccionadas</h4>
                            <p id="mobile-resumen-coladas" class="text-sm text-gray-600">—</p>
                        </div>
                    </div>

                    <!-- Botón de activación -->
                    <button type="button" id="mobile-btn-activar"
                        class="w-full px-4 py-3 bg-emerald-600 text-white rounded-lg font-bold text-lg">
                        Confirmar y Activar
                    </button>
                </div>
            </div>

        </div> <!-- Fin stepWrapper -->
    </div> <!-- Fin mobileStepContainer -->

    <!-- ========================================== -->
    <!-- MODAL DE EDICIÓN BOTTOM SHEET (MÓVIL) -->
    <!-- ========================================== -->
    <div id="mobileEditModal" class="mobile-edit-modal">
        <!-- Overlay -->
        <div class="mobile-modal-overlay absolute inset-0" onclick="cerrarModalEdicionMobile()"></div>

        <!-- Content -->
        <div class="mobile-edit-modal-content">
            <!-- Header -->
            <div
                class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between z-10">
                <h3 class="text-lg font-semibold text-gray-900">Editar Datos</h3>
                <button type="button" onclick="cerrarModalEdicionMobile()"
                    class="p-2 text-gray-400 hover:text-gray-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="mobile-edit-form" class="p-4 space-y-5 overflow-y-auto"
                style="max-height: calc(90vh - 120px);">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Tipo de compra</span>
                        <select id="edit-tipo-compra" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Selecciona tipo</option>
                            <option value="directo">Directo</option>
                            <option value="proveedor">Proveedor</option>
                            <option value="otro">Otro</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Proveedor</span>
                        <input type="text" id="edit-proveedor" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Distribuidor recomendado</span>
                        <input type="text" id="edit-distribuidor" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Albarán</span>
                        <input type="text" id="edit-albaran" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Fecha</span>
                        <input type="date" id="edit-fecha" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Pedido Cliente</span>
                        <input type="text" id="edit-pedido-cliente"
                            class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Pedido Código</span>
                        <input type="text" id="edit-pedido-codigo" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Peso total (kg)</span>
                        <input type="number" id="edit-peso-total" step="0.01"
                            class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Bultos total</span>
                        <input type="number" id="edit-bultos-total" class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-base font-semibold text-gray-900">Productos escaneados</h4>
                        <button type="button" onclick="agregarProductoMobile()"
                            class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-xs font-semibold">+
                            Añadir producto</button>
                    </div>
                    <div id="mobile-edit-products" class="space-y-4"></div>
                </div>
            </form>

            <!-- Footer con botones -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 px-4 py-3 flex gap-3">
                <button type="button" onclick="cerrarModalEdicionMobile()"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-medium">
                    Cancelar
                </button>
                <button type="button" onclick="guardarEdicionMobile()"
                    class="flex-1 px-4 py-2 bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white rounded-lg font-medium">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL DE PEDIDOS (MÓVIL) -->
    <!-- ========================================== -->
    <div id="mobilePedidosModal" class="mobile-edit-modal">
        <div class="mobile-modal-overlay" onclick="cerrarModalPedidosMobile()"></div>
        <div class="mobile-edit-modal-content">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Seleccionar Pedido</h3>
                <button type="button" onclick="cerrarModalPedidosMobile()"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Lista de pedidos -->
            <div id="mobile-lista-pedidos" class="p-4 space-y-3 overflow-y-auto" style="max-height: 60vh;">
                <p class="text-gray-500 text-center">Cargando pedidos...</p>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 px-4 py-3">
                <button type="button" onclick="cerrarModalPedidosMobile()"
                    class="w-full px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-medium">
                    Cerrar
                </button>
            </div>
        </div>
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
                                    <option value="ENCARRETADO">Encarretado</option>
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

        const formatLineaInfoText = (linea) => {
            if (!linea) {
                return '';
            }
            const parts = [];
            if (linea.pedido_codigo) {
                parts.push(`Pedido ${linea.pedido_codigo}`);
            }
            if (linea.producto) {
                parts.push(linea.producto);
            }
            if (linea.diametro) {
                parts.push(`Ø${linea.diametro}`);
            }
            if (linea.cantidad_pendiente) {
                parts.push(`${Number(linea.cantidad_pendiente).toLocaleString('es-ES')} kg`);
            }
            return parts.filter(Boolean).join(' • ');
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

            const infoElement = document.getElementById(`linea-info-${resultadoIdx}`);
            if (infoElement) {
                const text = formatLineaInfoText(linea);
                if (text) {
                    infoElement.textContent = text;
                    infoElement.dataset.lineaInfo = text;
                }
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
                statementText.innerHTML =
                    `El sistema recomendó <strong>${recommendedCode}</strong> pero preferiste <strong>${selectedCode}</strong>.`;
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
                    tipo_compra: null,
                    proveedor_texto: null,
                    distribuidor_recomendado: null,
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
                // console.log('Datos editados:', editedData);

                // Por ahora, solo ocultar y mostrar simulación
                document.getElementById(`confirmationPrompt-${idx}`).classList.add('hidden');
                document.getElementById(`simulationSection-${idx}`).classList.remove('hidden');
            });
        });
    </script>
    <div id="modal-coladas-activacion"
        class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm hidden items-center justify-center z-50 transition-all duration-300">
        <div
            class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl transform transition-all duration-300 overflow-hidden border border-gray-200">
            <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-5 border-b border-slate-600">
                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Confirmar activación de línea
                </h3>
                <p class="text-sm text-slate-300 mt-2">
                    Registrar coladas y bultos asociados (opcional)
                </p>
                <p id="modal-linea-info" class="text-sm text-slate-200 mt-1">Selecciona las coladas deseadas antes de
                    continuar.</p>
            </div>
            <div class="p-6">
                <div class="border border-gray-300 rounded-xl mb-5 shadow-sm bg-white overflow-hidden">
                    <table class="w-full text-sm table-fixed">
                        <colgroup>
                            <col style="width:45%">
                            <col style="width:25%">
                            <col style="width:30%">
                        </colgroup>
                        <thead class="bg-gradient-to-r from-gray-700 to-gray-800 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider text-xs">
                                    Colada</th>
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider text-xs">
                                    Bultos</th>
                                <th
                                    class="px-4 py-3 text-start font-semibold uppercase tracking-wider text-xs whitespace-nowrap">
                                    Peso (kg)</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-coladas-body-modal" class="divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-between items-center mb-6 pt-2">
                    <button type="button" id="btn-agregar-colada-modal"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 4v16m8-8H4"></path>
                        </svg>
                        Añadir colada / bulto
                    </button>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" id="btn-cancelar-coladas-modal"
                        class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-700 text-sm font-medium px-5 py-2.5 rounded-lg border border-gray-300 transition-all duration-200 shadow-sm hover:shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancelar
                    </button>
                    <button type="button" id="btn-confirmar-activacion-coladas-modal"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7"></path>
                        </svg>
                        Confirmar activación
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const modal = document.getElementById('modal-coladas-activacion');
            const cuerpoTabla = document.getElementById('tabla-coladas-body-modal');
            const btnAgregar = document.getElementById('btn-agregar-colada-modal');
            const btnCancelar = document.getElementById('btn-cancelar-coladas-modal');
            const btnConfirmar = document.getElementById('btn-confirmar-activacion-coladas-modal');
            const infoLinea = document.getElementById('modal-linea-info');

            const abrirModal = (coladas, lineaInfoText = '') => {
                cuerpoTabla.innerHTML = '';
                coladas.forEach(colada => {
                    cuerpoTabla.appendChild(crearFilaColada(colada));
                });
                if (infoLinea) {
                    infoLinea.textContent = lineaInfoText ?
                        lineaInfoText :
                        coladas.length ?
                        `Preparando ${coladas.length} colada(s) para activación.` :
                        'No hay coladas seleccionadas.';
                }
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const cerrarModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const crearFilaColada = (colada = {}) => {
                const row = document.createElement('tr');
                row.className = 'fila-colada hover:bg-gray-50 transition-colors duration-150';
                const valueColada = colada.colada || '';
                const valueBultos = colada.bultos || '';
                const valuePeso = colada.peso || '';
                row.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="text" class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm transition outline-none"
                            placeholder="Ej: 12/3456" value="${valueColada}">
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" step="1" min="0" class="w-full text-start border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm transition outline-none"
                            placeholder="0" value="${valueBultos}">
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" step="0.01" class="w-full text-start border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm transition outline-none"
                            placeholder="Peso (kg)" value="${valuePeso}">
                    </td>
                `;
                return row;
            };

            window.activarLineaSeleccionada = function(idx) {
                const checkboxes = document.querySelectorAll(`.colada-checkbox-${idx}:checked`);
                if (!checkboxes.length) {
                    return alert('Selecciona al menos una colada antes de activar la línea.');
                }
                const coladas = Array.from(checkboxes).map(cb => ({
                    colada: cb.dataset.colada || '',
                    bultos: cb.dataset.bultos || '',
                    peso: cb.dataset.peso || ''
                }));
                const infoElement = document.getElementById(`linea-info-${idx}`);
                const lineaInfoText = infoElement?.dataset?.lineaInfo || infoElement?.textContent || '';
                abrirModal(coladas, lineaInfoText);
            };

            if (btnAgregar) {
                btnAgregar.addEventListener('click', () => {
                    cuerpoTabla.appendChild(crearFilaColada());
                });
            }

            btnCancelar?.addEventListener('click', () => {
                cerrarModal();
            });

            btnConfirmar?.addEventListener('click', () => {
                cerrarModal();
            });
        })();
    </script>
    <div id="previewModal"
        class="fixed inset-0 z-50 items-center justify-center bg-black/70 opacity-0 pointer-events-none transition-opacity duration-200">
        <div class="preview-modal-content relative w-full max-w-3xl bg-black rounded-2xl shadow-2xl overflow-hidden">
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

            const mobilePreviewContainer = document.getElementById('mobile-preview-container');
            mobilePreviewContainer?.addEventListener('click', () => {
                const previewImg = document.getElementById('mobile-preview-img');
                if (previewImg && previewImg.src) {
                    openPreview(previewImg.src);
                }
            });

            window.openPreviewModal = openPreview;
        });

        // ========================================
        // SISTEMA DE NAVEGACIÓN MÓVIL
        // ========================================
        window.mobileStepManager = {
            currentStep: 1,
            maxStep: 1,
            totalSteps: 5,
            dataCache: {},

            init: function() {
                this.attachEventListeners();
                this.updateNavigation();
                this.updateProgressBar();
            },

            goToStep: function(stepNumber, forceAdvance = false) {
                if (stepNumber < 1 || stepNumber > this.totalSteps) return;

                // Permitir: retroceder, avanzar 1 paso, o avance forzado desde AJAX
                const isGoingBack = stepNumber < this.currentStep;
                const isNextStep = stepNumber === this.currentStep + 1;
                const canNavigate = isGoingBack || isNextStep || forceAdvance || stepNumber <= this.maxStep;

                if (!canNavigate) {
                    // console.log('Navegación bloqueada:', {
                    //     stepNumber,
                    //     currentStep: this.currentStep,
                    //     maxStep: this.maxStep
                    // });
                    return;
                }

                const wrapper = document.getElementById('stepWrapper');
                if (!wrapper) return;

                const translatePercentage = -(stepNumber - 1) * 20; // -0%, -20%, -40%, -60%, -80%

                wrapper.style.transform = `translateX(${translatePercentage}%)`;
                this.currentStep = stepNumber;

                // Actualizar maxStep si avanzamos
                if (stepNumber > this.maxStep) {
                    this.maxStep = stepNumber;
                }

                // console.log('Navegado a paso:', stepNumber, 'maxStep:', this.maxStep);

                this.updateNavigation();
                this.updateProgressBar();
            },

            next: function(forceAdvance = false) {
                if (this.currentStep < this.totalSteps) {
                    this.goToStep(this.currentStep + 1, forceAdvance);
                }
            },

            back: function() {
                if (this.currentStep > 1) {
                    this.goToStep(this.currentStep - 1);
                }
            },

            updateNavigation: function() {
                // Mostrar/ocultar botón retroceder
                const backBtn = document.getElementById('mobile-back-btn');
                if (backBtn) {
                    if (this.currentStep > 1) {
                        backBtn.classList.remove('hidden');
                    } else {
                        backBtn.classList.add('hidden');
                    }
                }

                // Actualizar título del header
                const titles = ['Subir Albarán', 'Confirmar Datos', 'Pedido', 'Coladas', 'Activación'];
                const titleElement = document.getElementById('mobile-step-title');
                if (titleElement && titles[this.currentStep - 1]) {
                    titleElement.textContent = titles[this.currentStep - 1];
                }

                // Actualizar indicador de paso
                const currentStepElement = document.getElementById('mobile-current-step');
                if (currentStepElement) {
                    currentStepElement.textContent = this.currentStep;
                }
            },

            updateProgressBar: function() {
                const progressBar = document.getElementById('mobile-progress-bar');
                if (progressBar) {
                    const percentage = (this.currentStep / this.totalSteps) * 100;
                    progressBar.style.width = `${percentage}%`;
                }
            },

            attachEventListeners: function() {
                // Botón retroceder
                const backBtn = document.getElementById('mobile-back-btn');
                if (backBtn) {
                    backBtn.addEventListener('click', () => this.back());
                }

                // Botones de continuar en cada vista
                document.querySelectorAll('[data-mobile-next]').forEach(btn => {
                    btn.addEventListener('click', () => this.next());
                });
            }
        };

        // Inicializar el sistema móvil al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si estamos en vista móvil
            const isMobile = window.innerWidth < 768;
            // console.log('Window width:', window.innerWidth, 'isMobile:', isMobile);

            if (window.mobileStepManager && isMobile) {
                // console.log('Inicializando mobileStepManager...');
                window.mobileStepManager.init();

                // Event listener para botón de activación
                const btnActivar = document.getElementById('mobile-btn-activar');
                if (btnActivar) {
                    btnActivar.addEventListener('click', confirmarActivacionMobile);
                    // console.log('Event listener de activación añadido');
                }
            } else if (!isMobile) {
                // console.log('Vista desktop detectada - sistema móvil no inicializado');
            }
        });

        // Re-inicializar si cambia el tamaño de ventana
        window.addEventListener('resize', function() {
            const isMobile = window.innerWidth < 768;
            if (window.mobileStepManager && isMobile && !window.mobileStepManager.initialized) {
                // console.log('Cambiado a móvil - inicializando...');
                window.mobileStepManager.init();
                window.mobileStepManager.initialized = true;
            }
        });

        /**
         * Confirmar y ejecutar activación
         */
        function confirmarActivacionMobile() {
            const cache = window.mobileStepManager.dataCache;
            const coladas = cache.coladasSeleccionadas || [];

            if (coladas.length === 0) {
                alert('Por favor selecciona al menos una colada para recepcionar');
                return;
            }

            // Aquí iría la lógica de activación real
            // Por ahora solo mostramos confirmación
            if (confirm('¿Confirmar la activación de este albarán?')) {
                alert('✅ Activación completada con éxito!\n\n' +
                    'En producción, aquí se ejecutaría la lógica de activación del pedido.\n\n' +
                    `- ${coladas.length} coladas procesadas\n` +
                    `- ${cache.totalesColadas?.bultos || 0} bultos\n` +
                    `- ${(cache.totalesColadas?.peso || 0).toLocaleString('es-ES')} kg`);

                // Resetear y volver a Vista 1
                window.mobileStepManager.dataCache = {};
                window.mobileStepManager.currentStep = 1;
                window.mobileStepManager.maxStep = 1;
                window.mobileStepManager.goToStep(1);

                // Limpiar formulario
                const form = document.getElementById('ocrForm-mobile');
                if (form) form.reset();
            }
        }

        // ========================================
        // FUNCIONES AJAX PARA MÓVIL
        // ========================================

        const mobileImageInput = document.getElementById('imagenes-mobile');
        const mobileProcessBtn = document.getElementById('processBtn-mobile');
        const processingIndicator = document.getElementById('processing-mobile');
        const processLabel = document.getElementById('processBtnLabel-mobile');
        const activeButtonClasses = ['bg-blue-600', 'hover:bg-blue-700', 'text-white'];
        const disabledButtonClasses = ['bg-gray-300', 'text-gray-500'];
        let isProcessingMobile = false;

        const setMobileButtonAppearance = (enabled) => {
            if (!mobileProcessBtn) return;
            activeButtonClasses.forEach((cls) => mobileProcessBtn.classList.remove(cls));
            disabledButtonClasses.forEach((cls) => mobileProcessBtn.classList.remove(cls));
            if (enabled) {
                mobileProcessBtn.disabled = false;
                mobileProcessBtn.classList.add(...activeButtonClasses);
            } else {
                mobileProcessBtn.disabled = true;
                mobileProcessBtn.classList.add(...disabledButtonClasses);
            }
        };

        const refreshMobileButton = () => {
            if (isProcessingMobile) return;
            const hasFile = mobileImageInput?.files?.length > 0;
            setMobileButtonAppearance(hasFile);
        };

        mobileImageInput?.addEventListener('change', refreshMobileButton);
        refreshMobileButton();

        const setProcessingState = (processing) => {
            isProcessingMobile = processing;
            if (!mobileProcessBtn) return;
            if (processing) {
                mobileProcessBtn.classList.remove(...activeButtonClasses);
                mobileProcessBtn.classList.add('bg-blue-800', 'hover:bg-blue-900', 'ring-2', 'ring-blue-400/70');
                mobileProcessBtn.disabled = true;
                processingIndicator?.classList.remove('hidden');
                // processLabel?.classList.add('hidden');
            } else {
                mobileProcessBtn.classList.remove('bg-blue-800', 'hover:bg-blue-900', 'ring-2', 'ring-blue-400/70');
                processingIndicator?.classList.add('hidden');
                // processLabel?.classList.remove('hidden');
                refreshMobileButton();
            }
        };

        /**
         * Procesar albarán via AJAX (móvil)
         */
        async function procesarAlbaranMobile() {
            const form = document.getElementById('ocrForm-mobile');
            const formData = new FormData(form);

            // Validar que se haya seleccionado proveedor y archivo
            const proveedor = document.getElementById('proveedor-mobile').value;
            const archivo = document.getElementById('imagenes-mobile').files[0];

            if (!proveedor) {
                alert('Por favor selecciona un proveedor');
                return;
            }

            if (!archivo) {
                alert('Por favor selecciona una imagen del albarán');
                return;
            }

            setProcessingState(true);

            try {
                const response = await fetch('/pruebasScanAlbaran/procesar-ajax', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                // console.log('Respuesta AJAX:', data);

                if (data.success && data.resultados && data.resultados.length > 0) {
                    // console.log('Datos recibidos correctamente');

                    // Guardar datos en cache
                    window.mobileStepManager.dataCache = {
                        resultado: data.resultados[0], // Primer resultado
                        distribuidores: data.distribuidores
                    };

                    // console.log('Cache guardado:', window.mobileStepManager.dataCache);

                    // Poblar Vista 2 con los datos recibidos
                    // console.log('Poblando Vista 2...');
                    poblarVista2ConDatos(data.resultados[0]);

                    // Avanzar a Vista 2 (forzar avance para permitir pasar de maxStep)
                    // console.log('Avanzando a Vista 2...');
                    window.mobileStepManager.next(true);

                    // console.log('Current step:', window.mobileStepManager.currentStep);
                } else {
                    console.error('Error en respuesta:', data);
                    alert('Error al procesar el albarán. Por favor, intenta de nuevo.');
                }

            } catch (error) {
                console.error('Error en petición AJAX:', error);
                alert('Error de conexión. Por favor, verifica tu conexión a internet.');
            } finally {
                setProcessingState(false);
            }
        }

        /**
         * Poblar Vista 2 con datos recibidos
         */
        function poblarVista2ConDatos(resultado) {
            const parsed = resultado.parsed || {};
            const sim = resultado.simulacion || {};
            console.log('mobile resultado', {
                parsed,
                sim
            });

            // Preview de imagen
            const previewContainer = document.getElementById('mobile-preview-container');
            const previewImg = document.getElementById('mobile-preview-img');
            if (previewImg && resultado.preview) {
                previewImg.src = resultado.preview;
                if (previewContainer) previewContainer.classList.remove('hidden');
            }

            // Datos generales
            const setTextContent = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '—';
            };

            setTextContent('mobile-albaran', parsed.albaran);
            setTextContent('mobile-fecha', parsed.fecha);
            setTextContent('mobile-peso-total',
                parsed.peso_total ? parseFloat(parsed.peso_total).toLocaleString('es-ES') + ' kg' : '—');
            setTextContent('mobile-bultos-total', parsed.bultos_total);
            setTextContent('mobile-tipo-compra', parsed.tipo_compra ? parsed.tipo_compra : '—');
            setTextContent('mobile-proveedor', parsed.proveedor_texto ? parsed.proveedor_texto : parsed.proveedor || '—');
            const linea = sim.linea_propuesta || {};
            const productosProcesados = Array.isArray(parsed.productos) ? parsed.productos : [];

            let descripcion = parsed.descripcion ? parsed.descripcion.trim() : '';
            if (!descripcion && productosProcesados.length) {
                const detecciones = [];
                productosProcesados.forEach(prod => {
                    const textoTratado = (prod.descripcion || prod.producto || '').toUpperCase();
                    if (textoTratado.includes('BARRA')) {
                        detecciones.push('BARRA');
                    } else if (textoTratado.includes('ENCARRETADO')) {
                        detecciones.push('ENCARRETADO');
                    }
                });

                const unicos = [...new Set(detecciones)];
                if (unicos.length === 1) {
                    descripcion = unicos[0];
                } else if (unicos.length > 1) {
                    descripcion = unicos.join(' y ');
                } else {
                    const primerProducto = productosProcesados[0];
                    descripcion = (primerProducto?.descripcion || primerProducto?.producto || '').trim();
                }
            }

            setTextContent('mobile-descripcion', descripcion);
            setTextContent('mobile-diametro', linea.diametro ? `Ø${linea.diametro}` : '—');
            setTextContent('mobile-longitud', linea.longitud ? `${linea.longitud} m` : '—');
            setTextContent('mobile-pedido-cliente-combo', parsed.pedido_cliente || '—');

            renderMobileProductColadas(productosProcesados);

            // Guardar en cache para siguientes vistas
            window.mobileStepManager.dataCache.parsed = parsed;
            window.mobileStepManager.dataCache.simulacion = sim;

            // Poblar Vista 3 (pedido)
            poblarVista3ConPedido(sim);

            // Poblar Vista 4 (coladas)
            poblarVista4ConColadas(sim);
        }

        /**
         * Poblar Vista 3 con información del pedido
         */
        function poblarVista3ConPedido(simulacion) {
            const container = document.getElementById('mobile-pedido-card');
            if (!container) return;

            const lineaPropuesta = simulacion.linea_propuesta;

            if (!lineaPropuesta) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-gray-500">No se encontró pedido sugerido</p>
                        <p class="text-sm text-gray-400 mt-1">Puedes seleccionar uno manualmente</p>
                    </div>
                `;
                return;
            }

            // Tipo de recomendación
            let badgeClass = 'bg-blue-100 text-blue-700';
            let badgeText = 'RECOMENDADO';

            if (lineaPropuesta.tipo_recomendacion === 'exacta') {
                badgeClass = 'bg-green-100 text-green-700';
                badgeText = 'COINCIDENCIA EXACTA';
            } else if (lineaPropuesta.tipo_recomendacion === 'parcial') {
                badgeClass = 'bg-yellow-100 text-yellow-700';
                badgeText = 'COINCIDENCIA PARCIAL';
            }

            container.innerHTML = `
                <div class="space-y-3">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${badgeClass}">
                        ${badgeText}
                    </span>

                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Pedido:</span>
                            <span class="font-semibold text-gray-900">${lineaPropuesta.pedido_codigo || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Fabricante:</span>
                            <span class="font-medium text-gray-900">${lineaPropuesta.fabricante || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Producto:</span>
                            <span class="font-medium text-gray-900">${lineaPropuesta.producto || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Obra:</span>
                            <span class="font-medium text-gray-900">${lineaPropuesta.obra || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Cantidad pendiente:</span>
                            <span class="font-medium text-gray-900">${lineaPropuesta.cantidad_pendiente || 0} kg</span>
                        </div>
                        ${lineaPropuesta.score ? `
                                                                                                                                                                                                                                                                                                <div class="mt-2 pt-2 border-t border-gray-200">
                                                                                                                                                                                                                                                                                                    <span class="text-gray-500">Score:</span>
                                                                                                                                                                                                                                                                                                    <span class="font-bold text-indigo-600">${Math.round(lineaPropuesta.score)}</span>
                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                ` : ''}
                    </div>
                </div>
            `;

            // Guardar línea seleccionada en cache
            window.mobileStepManager.dataCache.lineaSeleccionada = lineaPropuesta;
        }

        /**
         * Poblar Vista 4 con coladas a recepcionar
         */
        function poblarVista4ConColadas(simulacion) {
            const container = document.getElementById('mobile-coladas-container');
            if (!container) return;

            const bultos = simulacion.bultos_simulados || [];
            console.log(simulacion)

            if (bultos.length === 0) {
                container.innerHTML = '<p class="p-4 text-gray-500 text-center">No hay coladas disponibles</p>';
                return;
            }

            // Crear lista de coladas con checkboxes
            container.innerHTML = bultos.map((bulto, index) => `
                <label class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" class="mobile-colada-checkbox w-5 h-5 text-indigo-600 rounded"
                           data-colada="${bulto.colada || '—'}"
                           data-bultos="${bulto.bultos || 0}"
                           data-peso="${bulto.peso_kg || 0}"
                           onchange="actualizarTotalesColadas()"
                           checked>
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900">Colada ${bulto.colada || '-'}</span>
                            <span class="text-indigo-600 font-semibold text-lg">${bulto.bultos || 0} bultos</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <span>${bulto.peso_kg ? Number(bulto.peso_kg).toLocaleString('es-ES') + ' kg' : 'Peso no disponible'}</span>
                            <span class="text-xs uppercase tracking-wide text-gray-400">Disponible</span>
                        </div>
                    </div>
                </label>
            `).join('');

            // Actualizar totales iniciales (todas marcadas)
            actualizarTotalesColadas();

        }

        /**
         * Actualizar totales de coladas seleccionadas
         */
        function actualizarTotalesColadas() {
            const checkboxes = document.querySelectorAll('.mobile-colada-checkbox:checked');

            let totalBultos = 0;
            let totalPeso = 0;

            checkboxes.forEach(cb => {
                totalBultos += parseInt(cb.dataset.bultos) || 0;
                totalPeso += parseFloat(cb.dataset.peso) || 0;
            });

            // Actualizar UI
            const bultosEl = document.getElementById('mobile-bultos-seleccionados');
            const pesoEl = document.getElementById('mobile-peso-seleccionado');

            if (bultosEl) bultosEl.textContent = totalBultos;
            if (pesoEl) pesoEl.textContent = totalPeso.toLocaleString('es-ES') + ' kg';

            // Guardar en cache
            const coladasSeleccionadas = [];
            checkboxes.forEach(cb => {
                coladasSeleccionadas.push({
                    colada: cb.dataset.colada,
                    bultos: parseInt(cb.dataset.bultos) || 0,
                    peso_kg: parseFloat(cb.dataset.peso) || 0
                });
            });

            window.mobileStepManager.dataCache.coladasSeleccionadas = coladasSeleccionadas;
            window.mobileStepManager.dataCache.totalesColadas = {
                bultos: totalBultos,
                peso: totalPeso
            };

            // Poblar Vista 5 con resumen
            poblarVista5ConResumen();
        }

        const renderMobileProductColadas = (productos = []) => {
            const container = document.getElementById('mobile-product-groups');
            if (!container) return;
            if (!productos.length) {
                container.innerHTML = '<p class="text-xs text-gray-500">Respuesta AJAX: sin productos detectados.</p>';
                return;
            }

            const formatPeso = (valor) => {
                if (valor === undefined || valor === null || valor === '') {
                    return 'kg ?';
                }
                const numerico = Number(valor);
                if (Number.isNaN(numerico)) {
                    return 'kg ?';
                }
                return `${numerico.toLocaleString('es-ES')} kg`;
            };

            container.innerHTML = productos.map((producto) => {
                const lineItems = producto.line_items || [];
                const listItems = lineItems.map((colada) => `
                    <li class="flex justify-between text-[0.75rem] text-gray-700">
                        <span>${colada.colada || '—'}</span>
                        <span>${colada.bultos || 0} bultos</span>
                        <span class="text-gray-500">${formatPeso(colada.peso_kg ?? colada.peso)}</span>
                    </li>
                `).join('');

                return `
                    <div class="bg-white border border-gray-100 rounded-lg p-3 space-y-2 text-[0.8rem]">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold uppercase">${producto.descripcion || producto.producto || 'Producto'}</span>
                            <span class="text-gray-400">${producto.calidad || ''}</span>
                        </div>
                        <div class="flex gap-4 text-gray-500">
                            <span>Ø${producto.diametro || '—'}</span>
                            <span>${producto.longitud ? `${producto.longitud} m` : 'Longitud —'}</span>
                        </div>
                        <ul class="space-y-1 pt-2 border-t border-gray-100">
                            ${listItems}
                        </ul>
                    </div>
                `;
            }).join('');
        };

        /**
         * Poblar Vista 5 con resumen final
         */
        function poblarVista5ConResumen() {
            const cache = window.mobileStepManager.dataCache;
            const parsed = cache.parsed || {};
            const linea = cache.lineaSeleccionada || {};
            const coladas = cache.coladasSeleccionadas || [];
            const totales = cache.totalesColadas || {};

            // Actualizar datos del albarán
            const albaranEl = document.getElementById('mobile-resumen-albaran');
            const pedidoEl = document.getElementById('mobile-resumen-pedido');
            const coladasEl = document.getElementById('mobile-resumen-coladas');

            if (albaranEl) {
                albaranEl.textContent = `${parsed.albaran || '—'} (${parsed.fecha || '—'})`;
            }

            if (pedidoEl) {
                pedidoEl.textContent = `${linea.pedido_codigo || '—'} - ${linea.producto || '—'}`;
            }

            if (coladasEl) {
                if (coladas.length > 0) {
                    const resumen =
                        `${coladas.length} colada(s) - ${totales.bultos || 0} bultos - ${(totales.peso || 0).toLocaleString('es-ES')} kg`;
                    coladasEl.textContent = resumen;
                } else {
                    coladasEl.textContent = 'No hay coladas seleccionadas';
                }
            }
        }

        /**
         * Abrir modal de edición
         */
        /**
         * Abrir modal de edición
         */
        function abrirModalEdicionMobile() {
            const modal = document.getElementById('mobileEditModal');
            const cache = window.mobileStepManager.dataCache;
            const parsed = cache.parsed || {};

            document.getElementById('edit-tipo-compra').value = parsed.tipo_compra || '';
            document.getElementById('edit-proveedor').value = parsed.proveedor_texto || parsed.proveedor || '';
            document.getElementById('edit-distribuidor').value = parsed.distribuidor_recomendado || '';
            document.getElementById('edit-albaran').value = parsed.albaran || '';
            document.getElementById('edit-fecha').value = parsed.fecha || '';
            document.getElementById('edit-pedido-cliente').value = parsed.pedido_cliente || '';
            document.getElementById('edit-pedido-codigo').value = parsed.pedido_codigo || '';
            document.getElementById('edit-peso-total').value = parsed.peso_total || '';
            document.getElementById('edit-bultos-total').value = parsed.bultos_total || '';

            renderMobileEditProducts();

            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
        /**
         * Cerrar modal de edición
         */
        function cerrarModalEdicionMobile() {
            const modal = document.getElementById('mobileEditModal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        /**
         * Guardar cambios del modal
         */
        function guardarEdicionMobile() {
            const cache = window.mobileStepManager.dataCache;

            // Actualizar cache con valores editados
            if (!cache.parsed) cache.parsed = {};

            cache.parsed.tipo_compra = document.getElementById('edit-tipo-compra').value || null;
            cache.parsed.proveedor_texto = document.getElementById('edit-proveedor').value || null;
            cache.parsed.distribuidor_recomendado = document.getElementById('edit-distribuidor').value || null;
            cache.parsed.albaran = document.getElementById('edit-albaran').value;
            cache.parsed.fecha = document.getElementById('edit-fecha').value;
            cache.parsed.pedido_cliente = document.getElementById('edit-pedido-cliente').value;
            cache.parsed.pedido_codigo = document.getElementById('edit-pedido-codigo').value;
            cache.parsed.peso_total = document.getElementById('edit-peso-total').value;
            cache.parsed.bultos_total = document.getElementById('edit-bultos-total').value;

            const productos = [];
            document.querySelectorAll('.mobile-edit-product').forEach((productEl) => {
                const producto = {
                    descripcion: productEl.querySelector('[data-product-field=\"descripcion\"]').value || '',
                    diametro: productEl.querySelector('[data-product-field=\"diametro\"]').value || null,
                    longitud: productEl.querySelector('[data-product-field=\"longitud\"]').value || null,
                    calidad: productEl.querySelector('[data-product-field=\"calidad\"]').value || '',
                    line_items: []
                };

                productEl.querySelectorAll('.mobile-edit-colada').forEach((coladaEl) => {
                    producto.line_items.push({
                        colada: coladaEl.querySelector('[data-colada-field=\"colada\"]').value ||
                            '',
                        bultos: coladaEl.querySelector('[data-colada-field=\"bultos\"]').value ||
                            '',
                        peso_kg: coladaEl.querySelector('[data-colada-field=\"peso\"]').value || ''
                    });
                });

                productos.push(producto);
            });

            cache.parsed.productos = productos;

            // Actualizar resultado con los cambios
            if (cache.resultado) {
                cache.resultado.parsed = cache.parsed;
            }

            // Refrescar Vista 2 con nuevos datos
            poblarVista2ConDatos(cache.resultado);

            // Cerrar modal
            cerrarModalEdicionMobile();
        }

        function renderMobileEditProducts() {
            const container = document.getElementById('mobile-edit-products');
            const cache = window.mobileStepManager.dataCache;
            const productos = cache.parsed?.productos || [];
            if (!container) return;

            if (!productos.length) {
                container.innerHTML = '<p class="text-sm text-gray-500">No hay productos detectados aún.</p>';
                return;
            }

            const renderLineItems = (lineItems, productIndex) => {
                if (!lineItems.length) {
                    return '<p class="text-[0.75rem] text-gray-400">Sin coladas registradas.</p>';
                }

                return lineItems.map((colada, colIndex) => `
                    <div class="mobile-edit-colada grid grid-cols-12 gap-2 items-end" data-colada-index="${colIndex}">
                        <div class="col-span-5">
                            <span class="text-xs text-gray-500">Colada</span>
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" data-colada-field="colada" value="${colada.colada || ''}">
                        </div>
                        <div class="col-span-3">
                            <span class="text-xs text-gray-500">Bultos</span>
                            <input type="number" class="mt-1 w-full rounded-lg border-gray-300 text-sm" data-colada-field="bultos" value="${colada.bultos || ''}">
                        </div>
                        <div class="col-span-3">
                            <span class="text-xs text-gray-500">Peso (kg)</span>
                            <input type="number" step="0.01" class="mt-1 w-full rounded-lg border-gray-300 text-sm" data-colada-field="peso" value="${colada.peso_kg || ''}">
                        </div>
                        <div class="col-span-1 flex justify-end">
                            <button type="button" class="text-red-500 text-sm font-semibold" onclick="eliminarColadaMobile(${productIndex}, ${colIndex})">✕</button>
                        </div>
                    </div>
                `).join('');
            };

            container.innerHTML = productos.map((producto, productIndex) => `
                <div class="border border-gray-200 rounded-2xl p-4 bg-white space-y-3 mobile-edit-product" data-product-index="${productIndex}">
                    <div class="flex items-center justify-between">
                        <h5 class="text-sm font-semibold text-gray-900">Producto ${productIndex + 1}</h5>
                        <button type="button" class="text-xs text-red-500 font-bold" onclick="eliminarProductoMobile(${productIndex})">Eliminar</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="text-[0.75rem] text-gray-500">Descripción
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" data-product-field="descripcion" value="${producto.descripcion || ''}">
                        </label>
                        <label class="text-[0.75rem] text-gray-500">Diámetro
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" data-product-field="diametro" value="${producto.diametro || ''}">
                        </label>
                        <label class="text-[0.75rem] text-gray-500">Longitud
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" data-product-field="longitud" value="${producto.longitud || ''}">
                        </label>
                        <label class="text-[0.75rem] text-gray-500">Calidad
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" data-product-field="calidad" value="${producto.calidad || ''}">
                        </label>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Coladas</span>
                            <button type="button" class="text-xs font-semibold text-indigo-600" onclick="agregarColadaMobile(${productIndex})">+ Añadir colada</button>
                        </div>
                        <div class="space-y-2">
                            ${renderLineItems(producto.line_items || [], productIndex)}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function agregarProductoMobile() {
            const cache = window.mobileStepManager.dataCache;
            if (!cache.parsed) cache.parsed = {};
            cache.parsed.productos = cache.parsed.productos || [];
            cache.parsed.productos.push({
                descripcion: '',
                diametro: '',
                longitud: '',
                calidad: '',
                line_items: []
            });
            renderMobileEditProducts();
        }

        function eliminarProductoMobile(index) {
            const cache = window.mobileStepManager.dataCache;
            if (!cache.parsed?.productos) return;
            cache.parsed.productos.splice(index, 1);
            renderMobileEditProducts();
        }

        function agregarColadaMobile(productIndex) {
            const cache = window.mobileStepManager.dataCache;
            const productos = cache.parsed?.productos || [];
            if (!productos[productIndex]) return;
            productos[productIndex].line_items = productos[productIndex].line_items || [];
            productos[productIndex].line_items.push({
                colada: '',
                bultos: '',
                peso_kg: ''
            });
            renderMobileEditProducts();
        }

        function eliminarColadaMobile(productIndex, colIndex) {
            const cache = window.mobileStepManager.dataCache;
            const productos = cache.parsed?.productos || [];
            if (!productos[productIndex]?.line_items) return;
            productos[productIndex].line_items.splice(colIndex, 1);
            renderMobileEditProducts();
        }


        // ========================================
        // FUNCIONES MODAL DE PEDIDOS (MÓVIL)
        // ========================================

        /**
         * Abrir modal de pedidos
         */
        function abrirModalPedidosMobile() {
            const modal = document.getElementById('mobilePedidosModal');
            if (modal) {
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
                document.body.style.overflow = 'hidden';
            }

            // Poblar lista de pedidos
            poblarListaPedidosMobile();
        }

        /**
         * Cerrar modal de pedidos
         */
        function cerrarModalPedidosMobile() {
            const modal = document.getElementById('mobilePedidosModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 300);
                document.body.style.overflow = '';
            }
        }

        /**
         * Poblar lista de pedidos con líneas pendientes
         */
        function poblarListaPedidosMobile() {
            const container = document.getElementById('mobile-lista-pedidos');
            if (!container) return;

            const cache = window.mobileStepManager.dataCache;
            const simulacion = cache.simulacion || {};
            const lineasPendientes = simulacion.lineas_pendientes || [];

            if (lineasPendientes.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-gray-500 font-medium">No hay pedidos disponibles</p>
                        <p class="text-sm text-gray-400 mt-1">No se encontraron líneas de pedido pendientes</p>
                    </div>
                `;
                return;
            }

            // Crear cards de pedidos
            container.innerHTML = lineasPendientes.map((linea, index) => {
                const isSelected = cache.lineaSeleccionada && cache.lineaSeleccionada.id === linea.id;

                return `
                    <div class="bg-white border-2 ${isSelected ? 'border-indigo-600' : 'border-gray-200'} rounded-lg p-4 cursor-pointer hover:border-indigo-400 transition"
                         onclick="seleccionarPedidoMobile(${index})">
                        ${isSelected ? `
                                                                                                                                                                                                                                                                                                    <div class="flex items-center gap-2 mb-2">
                                                                                                                                                                                                                                                                                                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                                                                                                                                                                                                                                                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                                                                                                                                                                                                                                                        </svg>
                                                                                                                                                                                                                                                                                                        <span class="text-xs font-bold text-indigo-600 uppercase">Seleccionado</span>
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                ` : ''}

                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500">Pedido:</span>
                                <span class="ml-2 font-semibold text-gray-900">${linea.pedido_codigo || '—'}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Fabricante:</span>
                                <span class="ml-2 text-gray-900">${linea.fabricante || '—'}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Producto:</span>
                                <span class="ml-2 text-gray-900">${linea.producto || '—'}</span>
                            </div>
                            ${linea.obra ? `
                                                                                                                                                                                                                                                                                                        <div>
                                                                                                                                                                                                                                                                                                            <span class="text-gray-500">Obra:</span>
                                                                                                                                                                                                                                                                                                            <span class="ml-2 text-gray-900">${linea.obra}</span>
                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                    ` : ''}
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                <div>
                                    <span class="text-gray-500">Pendiente:</span>
                                    <span class="ml-2 font-bold text-gray-900">${linea.cantidad_pendiente || 0} kg</span>
                                </div>
                                ${linea.score ? `
                                                                                                                                                                                                                                                                                                            <div class="text-xs px-2 py-1 rounded-full ${
                                                                                                                                                                                                                                                                                                                linea.score >= 0.9 ? 'bg-green-100 text-green-700' :
                                                                                                                                                                                                                                                                                                                linea.score >= 0.7 ? 'bg-yellow-100 text-yellow-700' :
                                                                                                                                                                                                                                                                                                                'bg-blue-100 text-blue-700'
                                                                                                                                                                                                                                                                                                            }">
                                                                                                                                                                                                                                                                                                                Score: ${(linea.score * 100).toFixed(0)}%
                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                        ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        /**
         * Seleccionar pedido alternativo
         */
        function seleccionarPedidoMobile(index) {
            const cache = window.mobileStepManager.dataCache;
            const simulacion = cache.simulacion || {};
            const lineasPendientes = simulacion.lineas_pendientes || [];

            if (index < 0 || index >= lineasPendientes.length) return;

            const lineaSeleccionada = lineasPendientes[index];

            // Actualizar cache
            cache.lineaSeleccionada = lineaSeleccionada;

            // Actualizar Vista 3 con la nueva línea
            actualizarVista3ConLineaSeleccionada(lineaSeleccionada);

            // Cerrar modal
            cerrarModalPedidosMobile();
        }

        /**
         * Actualizar Vista 3 con línea seleccionada
         */
        function actualizarVista3ConLineaSeleccionada(linea) {
            const container = document.getElementById('mobile-pedido-card');
            if (!container) return;

            // Tipo de recomendación
            let badgeClass = 'bg-blue-100 text-blue-700';
            let badgeText = 'RECOMENDADO';

            if (linea.tipo_recomendacion === 'exacta') {
                badgeClass = 'bg-green-100 text-green-700';
                badgeText = 'COINCIDENCIA EXACTA';
            } else if (linea.tipo_recomendacion === 'parcial') {
                badgeClass = 'bg-yellow-100 text-yellow-700';
                badgeText = 'COINCIDENCIA PARCIAL';
            }

            container.innerHTML = `
                <div class="space-y-3">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${badgeClass}">
                        ${badgeText}
                    </span>

                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Pedido:</span>
                            <span class="ml-2 font-semibold text-gray-900">${linea.pedido_codigo || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Fabricante:</span>
                            <span class="ml-2 text-gray-900">${linea.fabricante || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Producto:</span>
                            <span class="ml-2 text-gray-900">${linea.producto || '—'}</span>
                        </div>
                        ${linea.obra ? `
                                                                                                                                                                                                                                                                                                    <div>
                                                                                                                                                                                                                                                                                                        <span class="text-gray-500">Obra:</span>
                                                                                                                                                                                                                                                                                                        <span class="ml-2 text-gray-900">${linea.obra}</span>
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                ` : ''}
                        <div class="pt-2 border-t border-gray-100">
                            <span class="text-gray-500">Cantidad Pendiente:</span>
                            <span class="ml-2 font-bold text-gray-900">${linea.cantidad_pendiente || 0} kg</span>
                        </div>
                        ${linea.score ? `
                                                                                                                                                                                                                                                                                                    <div class="text-xs text-gray-500">
                                                                                                                                                                                                                                                                                                        Score de coincidencia: ${(linea.score * 100).toFixed(1)}%
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                ` : ''}
                    </div>
                </div>
            `;
        }

        // Añadir event listener al botón "Ver otros pedidos"
        document.addEventListener('DOMContentLoaded', function() {
            const btnVerOtrosPedidos = document.getElementById('mobile-ver-otros-pedidos');
            if (btnVerOtrosPedidos) {
                btnVerOtrosPedidos.addEventListener('click', abrirModalPedidosMobile);
            }
        });
    </script>

    <script>
        (function() {
            const breakpointClasses = ['py-6', 'px-0', 'sm:px-6', 'lg:px-8'];

            const syncAppContentPadding = () => {
                const appContent = document.getElementById('app_content');
                if (!appContent) return;
                const isMobileView = window.innerWidth < 1024;
                if (isMobileView) {
                    appContent.classList.remove(...breakpointClasses);
                    appContent.classList.add('h-[calc(100vh-57px)]');
                } else {
                    breakpointClasses.forEach((klass) => appContent.classList.add(klass));
                }
            };

            window.addEventListener('resize', syncAppContentPadding);
            document.addEventListener('DOMContentLoaded', syncAppContentPadding);
        })();
    </script>
</x-app-layout>
