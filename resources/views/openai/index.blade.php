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
                    <p class="text-sm text-gray-600 mt-1">Sube una imagen del albarán para ver qué línea de pedido se activaría y qué bultos se crearían</p>
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
                        Procesando con IA...
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
                                            {{ $sim['proveedor_nombre'] ?? 'Proveedor desconocido' }}
                                        </span>
                                        <span class="simulacion-badge badge-warning">
                                            {{ $sim['bultos_albaran'] ?? 0 }} bultos
                                        </span>
                                        @if($sim['peso_total'])
                                            <span class="simulacion-badge badge-info">
                                                {{ number_format($sim['peso_total'], 0, ',', '.') }} kg
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
                        <div class="p-6 space-y-6">
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
                                                        <th class="px-4 py-2 text-left font-medium">Creada</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach ($sim['lineas_pendientes'] as $linea)
                                                        <tr class="hover:bg-gray-50 {{ $loop->first ? 'bg-green-50' : '' }}">
                                                            <td class="px-4 py-3 font-medium text-gray-900">
                                                                {{ $linea['codigo'] }}
                                                                @if($loop->first)
                                                                    <span class="ml-2 text-xs bg-green-600 text-white px-2 py-0.5 rounded-full">PROPUESTA</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-700">{{ $linea['pedido_codigo'] }}</td>
                                                            <td class="px-4 py-3 text-gray-700">{{ $linea['producto'] }}</td>
                                                            <td class="px-4 py-3 text-gray-700">{{ $linea['obra'] }}</td>
                                                            <td class="px-4 py-3 text-center font-semibold text-gray-900">
                                                                {{ $linea['cantidad_pendiente'] }} / {{ $linea['cantidad'] }}
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <span class="text-xs px-2 py-1 rounded-full {{ $linea['estado'] === 'activo' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                                                    {{ ucfirst($linea['estado']) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-600">{{ $linea['fecha_creacion'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    @if($sim['linea_propuesta'])
                                        <div class="mt-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                                            <p class="text-sm text-green-800">
                                                <strong>✓ Se propone activar:</strong> {{ $sim['linea_propuesta']['codigo'] }}
                                                (es la más antigua y tiene {{ $sim['linea_propuesta']['cantidad_pendiente'] }} bultos pendientes)
                                            </p>
                                        </div>
                                    @endif
                                @else
                                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <p class="text-sm text-yellow-800">
                                            ⚠️ No se encontraron líneas de pedido pendientes para este proveedor/producto
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Simulación: Bultos a Crear -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 mb-3">
                                    📦 Bultos que se crearían
                                </h3>
                                @if(count($sim['bultos_simulados']) > 0)
                                    <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-100 text-gray-700">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-medium">#</th>
                                                        <th class="px-4 py-2 text-left font-medium">Colada</th>
                                                        <th class="px-4 py-2 text-right font-medium">Peso (kg)</th>
                                                        <th class="px-4 py-2 text-left font-medium">Acción simulada</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach ($sim['bultos_simulados'] as $bulto)
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $bulto['numero'] }}</td>
                                                            <td class="px-4 py-3 text-gray-700 font-mono">{{ $bulto['colada'] }}</td>
                                                            <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                                                {{ $bulto['peso_kg'] ? number_format($bulto['peso_kg'], 2, ',', '.') : '—' }}
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                                                                    {{ $bulto['estado_simulado'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600">No se detectaron bultos en el albarán</p>
                                @endif
                            </div>

                            <!-- Simulación: Estado Final -->
                            @if($sim['estado_final_simulado'])
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 mb-3">
                                        📊 Estado final simulado de la línea
                                    </h3>
                                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Cantidad recepcionada</p>
                                                <p class="text-2xl font-bold text-indigo-900">
                                                    {{ $sim['estado_final_simulado']['cantidad_recepcionada_nueva'] }}
                                                    <span class="text-sm text-indigo-600">/ {{ $sim['estado_final_simulado']['cantidad_total'] }}</span>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Progreso</p>
                                                <p class="text-2xl font-bold text-indigo-900">{{ $sim['estado_final_simulado']['progreso'] }}%</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Nuevo estado</p>
                                                <p class="text-lg font-bold {{ $sim['estado_final_simulado']['estado_nuevo'] === 'completado' ? 'text-green-600' : 'text-blue-600' }}">
                                                    {{ ucfirst($sim['estado_final_simulado']['estado_nuevo']) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Selector de línea alternativa -->
                            @if($sim['hay_coincidencias'] && count($sim['lineas_pendientes']) > 1)
                                <div class="border-t border-gray-200 pt-4">
                                    <label class="text-sm font-medium text-gray-700">
                                        ¿Prefieres activar otra línea?
                                        <select class="mt-2 w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            @foreach ($sim['lineas_pendientes'] as $linea)
                                                <option value="{{ $linea['id'] }}" {{ $loop->first ? 'selected' : '' }}>
                                                    {{ $linea['codigo'] }} - {{ $linea['producto'] }} ({{ $linea['cantidad_pendiente'] }} pendientes)
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            @endif

                            <!-- Aviso -->
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <p class="text-sm text-amber-800">
                                    <strong>⚠️ Esto es una simulación:</strong> No se ha modificado nada en la base de datos.
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
    </script>
</x-app-layout>
