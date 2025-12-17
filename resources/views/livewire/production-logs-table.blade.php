<div wire:poll.5s id="production-logs-component">
    <div class="w-full px-6 py-4">

        <!-- Selector de archivo de logs -->
        <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 border-l-4 border-indigo-500 rounded-r-lg p-3 mb-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-semibold text-indigo-800">📅 Archivo de Logs:</label>
                <select wire:model.live="selectedFile"
                    class="border-indigo-300 rounded-md shadow-sm text-sm px-3 py-1.5 bg-white text-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                    @foreach ($logFiles as $file)
                        <option value="{{ basename($file['path']) }}">
                            {{ basename($file['path']) }}
                            ({{ \Carbon\Carbon::createFromTimestamp($file['modified'])->format('d/m/Y') }})
                            - {{ number_format($file['size'] / 1024, 2) }} KB
                        </option>
                    @endforeach
                </select>
                <span class="text-xs text-indigo-600 font-medium">
                    Total de archivos: {{ count($logFiles) }}
                </span>
            </div>
        </div>

        <x-tabla.filtros-aplicados :filtros="$this->getFiltrosActivos()" />

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg text-xs">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <x-tabla.encabezado-ordenable campo="Fecha y Hora" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha" class="w-24" />
                        <x-tabla.encabezado-ordenable campo="Acción" :sortActual="$sort" :orderActual="$order" texto="Acción"
                            class="w-24" />
                        <x-tabla.encabezado-ordenable campo="Usuario" :sortActual="$sort" :orderActual="$order"
                            texto="Usuario" class="w-20" />
                        <x-tabla.encabezado-ordenable campo="Usuario 2" :sortActual="$sort" :orderActual="$order"
                            texto="Usr 2" class="w-20" />
                        <x-tabla.encabezado-ordenable campo="Etiqueta" :sortActual="$sort" :orderActual="$order"
                            texto="Etiq." class="w-16" />
                        <x-tabla.encabezado-ordenable campo="Planilla" :sortActual="$sort" :orderActual="$order"
                            texto="Planilla" class="w-20" />
                        <x-tabla.encabezado-ordenable campo="Obra" :sortActual="$sort" :orderActual="$order"
                            texto="Obra" class="w-24" />
                        <x-tabla.encabezado-ordenable campo="Cliente" :sortActual="$sort" :orderActual="$order"
                            texto="Cliente" class="w-24" />
                        <x-tabla.encabezado-ordenable campo="Nave" :sortActual="$sort" :orderActual="$order"
                            texto="Nave" class="w-16" />
                        <x-tabla.encabezado-ordenable campo="Máquina" :sortActual="$sort" :orderActual="$order"
                            texto="Máq." class="w-16" />
                        <th class="p-1 border w-16">Estado</th>
                        <x-tabla.encabezado-ordenable campo="Peso Estimado (kg)" :sortActual="$sort" :orderActual="$order"
                            texto="Peso" class="w-14" />
                        <x-tabla.encabezado-ordenable campo="Paquete" :sortActual="$sort" :orderActual="$order"
                            texto="Paq." class="w-16" />
                        <th class="p-1 border w-12">Obs.</th>
                        <th class="p-1 border w-12">Traz.</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="fecha"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Fecha...">
                        </th>
                        <th class="p-1 border">
                            <select wire:model.live="accion"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todas</option>
                                <option value="INICIO FABRICACIÓN">INICIO FABRICACIÓN</option>
                                <option value="CAMBIO ESTADO FABRICACIÓN">CAMBIO ESTADO FABRICACIÓN</option>
                                <option value="CREAR PAQUETE">CREAR PAQUETE</option>
                                <option value="AÑADIR A PAQUETE">AÑADIR A PAQUETE</option>
                                <option value="QUITAR DE PAQUETE">QUITAR DE PAQUETE</option>
                                <option value="ELIMINAR PAQUETE">ELIMINAR PAQUETE</option>
                            </select>
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="usuario"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Usuario...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="usuario2"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Usuario 2...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="etiqueta"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Etiqueta...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="planilla"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Planilla...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="obra"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Obra...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cliente...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="nave"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Nave...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="maquina"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Máquina...">
                        </th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="paquete"
                                class="w-full text-xs border rounded px-1 py-0.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Paquete...">
                        </th>
                        <th class="p-1 border text-center align-middle">
                            <button wire:click="limpiarFiltros"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>
                        </th>
                        <th class="p-1 border"></th>
                    </tr>
                </thead>

                <tbody class="text-gray-700">
                    @forelse ($logs as $log)
                        @php
                            // Colores por tipo de acción
                            $actionColors = [
                                'INICIO FABRICACIÓN' => 'bg-blue-100 text-blue-800',
                                'CAMBIO ESTADO FABRICACIÓN' => 'bg-yellow-100 text-yellow-800',
                                'CREAR PAQUETE' => 'bg-green-100 text-green-800',
                                'AÑADIR A PAQUETE' => 'bg-purple-100 text-purple-800',
                                'QUITAR DE PAQUETE' => 'bg-orange-100 text-orange-800',
                                'ELIMINAR PAQUETE' => 'bg-red-100 text-red-800',
                            ];
                            $actionClass = $actionColors[$log['Acción']] ?? 'bg-gray-100 text-gray-800';

                            // Verificar si es un log nuevo
                            $isNew = in_array($log['Fecha y Hora'], $newLogIds ?? []);
                        @endphp
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 text-xs leading-tight transition-colors {{ $isNew ? 'new-log-row bg-green-300 animate-pulse' : '' }}"
                            data-log-id="{{ $log['Fecha y Hora'] }}">
                            <td class="px-0.5 py-1 text-center border font-mono text-xs">
                                {{ \Carbon\Carbon::parse($log['Fecha y Hora'])->format('d/m H:i') }}</td>
                            <td class="px-0.5 py-1 text-center border">
                                <span class="px-1 py-0.5 rounded text-xs font-medium {{ $actionClass }}">
                                    {{ str_replace(['FABRICACIÓN', 'PAQUETE'], ['FAB.', 'PAQ.'], $log['Acción']) }}
                                </span>
                            </td>
                            <td class="px-0.5 py-1 text-center border text-blue-600 truncate max-w-[80px]"
                                title="{{ $log['Usuario'] ?? 'Sistema' }}">
                                {{ \Illuminate\Support\Str::limit($log['Usuario'] ?? 'Sistema', 12, '') }}
                            </td>
                            <td class="px-0.5 py-1 text-center border text-green-600 truncate max-w-[80px]"
                                title="{{ $log['Usuario 2'] ?? '-' }}">
                                {{ $log['Usuario 2'] && $log['Usuario 2'] !== '' ? \Illuminate\Support\Str::limit($log['Usuario 2'], 12, '') : '-' }}
                            </td>
                            <td class="px-0.5 py-1 text-center border font-semibold text-xs">
                                {{ $log['Etiqueta'] ?? '-' }}</td>
                            <td class="px-0.5 py-1 text-center border text-xs">{{ $log['Planilla'] ?? '-' }}</td>
                            <td class="px-0.5 py-1 text-center border truncate max-w-[90px]"
                                title="{{ $log['Obra'] ?? '-' }}">
                                {{ \Illuminate\Support\Str::limit($log['Obra'] ?? '-', 15, '') }}</td>
                            <td class="px-0.5 py-1 text-center border truncate max-w-[90px]"
                                title="{{ $log['Cliente'] ?? '-' }}">
                                {{ \Illuminate\Support\Str::limit($log['Cliente'] ?? '-', 15, '') }}</td>
                            <td class="px-0.5 py-1 text-center border text-xs">{{ $log['Nave'] ?? '-' }}</td>
                            <td class="px-0.5 py-1 text-center border text-xs">{{ $log['Máquina'] ?? '-' }}</td>
                            <td class="px-0.5 py-1 text-center border">
                                <div class="text-xs leading-tight">
                                    <div class="text-gray-500 text-xs">
                                        {{ \Illuminate\Support\Str::limit($log['Estado Inicial'] ?? '-', 8, '') }}
                                    </div>
                                    @if (isset($log['Estado Final']) && $log['Estado Final'] !== $log['Estado Inicial'])
                                        <div class="text-gray-400 text-xs">↓</div>
                                        <div class="text-gray-900 font-medium text-xs">
                                            {{ \Illuminate\Support\Str::limit($log['Estado Final'], 8, '') }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-0.5 py-1 text-right border text-xs">{{ $log['Peso Estimado (kg)'] ?? '-' }}
                            </td>
                            <td class="px-0.5 py-1 text-center border font-mono text-xs">{{ $log['Paquete'] ?? '-' }}
                            </td>
                            <td class="px-0.5 py-1 text-center border">
                                @if (isset($log['Observaciones']) && $log['Observaciones'] !== '-' && !empty(trim($log['Observaciones'])))
                                    <button
                                        onclick="mostrarObservaciones({{ json_encode($log['Observaciones']) }}, '{{ $log['Etiqueta'] ?? 'N/A' }}', '{{ $log['Acción'] ?? 'N/A' }}')"
                                        class="px-1 py-0.5 bg-indigo-600 text-white rounded hover:bg-indigo-700 inline-flex items-center"
                                        title="Ver observaciones">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-0.5 py-1 text-center border">
                                @if (isset($log['Etiqueta']) && $log['Etiqueta'] !== '-')
                                    <button onclick="filtrarPorEtiqueta('{{ $log['Etiqueta'] }}')"
                                        class="px-1 py-0.5 bg-blue-600 text-white rounded hover:bg-blue-700 inline-flex items-center"
                                        title="Ver todos los logs de esta etiqueta">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-8 text-gray-500">
                                No hay registros de producción para mostrar
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Estadísticas y acciones -->
        <div class="mt-4 bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-r-lg p-3">
            <div class="flex justify-between items-center gap-4 text-sm text-gray-700">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">Registros por página:</span>
                        <select wire:model.live="perPage"
                            class="border-gray-300 rounded-md shadow-sm text-sm px-2 py-1">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>
                    @if ($total > 0)
                        <div>
                            <span class="font-semibold">Total:</span>
                            <span
                                class="text-base font-bold text-blue-800">{{ number_format($total, 0, ',', '.') }}</span>
                            <span class="text-gray-500">registros</span>
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if (count($logFiles) > 0)
                        @php
                            $currentFile = collect($logFiles)->firstWhere(function ($file) {
                                return basename($file['path']) ===
                                    ($this->selectedFile ??
                                        basename(\App\Services\ProductionLogger::getCurrentLogPath()));
                            });
                        @endphp
                        @if ($currentFile)
                            <a href="{{ route('production.logs.download', basename($currentFile['path'])) }}"
                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm inline-flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descargar CSV
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Paginación Livewire -->
        <div class="mt-4">
            {{ $logs->links('vendor.livewire.tailwind') }}
        </div>
    </div>
</div>

@push('scripts')
    <script>
        /**
         * Muestra el modal con las observaciones
         */
        window.mostrarObservaciones = function(observaciones, etiqueta, accion) {
            const modal = document.getElementById('modal-observaciones');
            const contenido = document.getElementById('modal-observaciones-contenido');
            const etiquetaEl = document.getElementById('modal-etiqueta');
            const accionEl = document.getElementById('modal-accion');

            if (modal && contenido && etiquetaEl && accionEl) {
                etiquetaEl.textContent = etiqueta;
                accionEl.textContent = accion;
                contenido.textContent = observaciones;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Evitar scroll del body
            }
        };

        /**
         * Cierra el modal de observaciones
         */
        window.cerrarModalObservaciones = function() {
            const modal = document.getElementById('modal-observaciones');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = ''; // Restaurar scroll del body
            }
        };

        /**
         * Cerrar modal con tecla ESC
         */
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalObservaciones();
            }
        });

        /**
         * Filtra la tabla para mostrar solo los logs de una etiqueta específica
         */
        window.filtrarPorEtiqueta = function(etiqueta) {
            console.log('Filtrando por etiqueta:', etiqueta);

            // Buscar el input de filtro de etiqueta
            const inputEtiqueta = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="etiqueta"]');

            if (inputEtiqueta) {
                // Establecer el valor del filtro
                inputEtiqueta.value = etiqueta;

                // Disparar el evento de input para que Livewire lo detecte
                inputEtiqueta.dispatchEvent(new Event('input', {
                    bubbles: true
                }));

                // Scroll suave al input para que el usuario vea el filtro aplicado
                inputEtiqueta.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Resaltar brevemente el input
                inputEtiqueta.classList.add('ring-2', 'ring-blue-500');
                setTimeout(() => {
                    inputEtiqueta.classList.remove('ring-2', 'ring-blue-500');
                }, 2000);

                console.log('✅ Filtro aplicado a etiqueta:', etiqueta);
            } else {
                console.error('No se encontró el input de filtro de etiqueta');
                alert('Error: No se pudo aplicar el filtro');
            }
        };

        function initProductionLogsTablePage() {
            // Prevenir doble inicialización
            if (document.body.dataset.productionLogsTablePageInit === 'true') return;

            console.log('🔍 Inicializando tabla de logs de producción...');

            // Remover señalización de logs nuevos después de 25 segundos
            function removeNewLogHighlight() {
                const newLogs = document.querySelectorAll('.new-log-row');
                newLogs.forEach(row => {
                    setTimeout(() => {
                        row.classList.remove('new-log-row', 'bg-green-300', 'animate-pulse');
                    }, 25000); // 25 segundos
                });
            }

            // Ejecutar al cargar la página
            removeNewLogHighlight();

            // Ejecutar cada vez que Livewire actualiza el componente
            document.addEventListener('livewire:update', removeNewLogHighlight);

            // Marcar como inicializado
            document.body.dataset.productionLogsTablePageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers.push(initProductionLogsTablePage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initProductionLogsTablePage);
        document.addEventListener('DOMContentLoaded', initProductionLogsTablePage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.productionLogsTablePageInit = 'false';
        });
    </script>
@endpush
</div>
