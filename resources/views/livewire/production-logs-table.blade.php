<div wire:poll.5s id="production-logs-component">
    <div class="w-full px-6 py-4">

        <!-- Selector de archivo de logs -->
        <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 border-l-4 border-indigo-500 rounded-r-lg p-3 mb-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-semibold text-indigo-800 dark:text-indigo-300">Archivo de Logs:</label>
                <select wire:model.live="selectedFile"
                    class="border-indigo-300 dark:border-indigo-600 rounded-md shadow-sm text-sm px-3 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                    @foreach ($logFiles as $file)
                        <option value="{{ basename($file['path']) }}">
                            {{ basename($file['path']) }}
                            ({{ \Carbon\Carbon::createFromTimestamp($file['modified'])->format('d/m/Y') }})
                            - {{ number_format($file['size'] / 1024, 2) }} KB
                        </option>
                    @endforeach
                </select>
                <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                    Total de archivos: {{ count($logFiles) }}
                </span>
            </div>
        </div>

        <x-tabla.filtros-aplicados :filtros="$this->getFiltrosActivos()" />

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white dark:bg-gray-900 shadow-lg rounded-lg">
            <table class="table-global w-full min-w-[1200px] text-xs">
                <thead>
                    <tr class="text-center">
                        <x-tabla.encabezado-ordenable campo="Fecha y Hora" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Acción" :sortActual="$sort" :orderActual="$order" texto="Acción"
                            padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Usuario" :sortActual="$sort" :orderActual="$order"
                            texto="Usuario" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Usuario 2" :sortActual="$sort" :orderActual="$order"
                            texto="Usr 2" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Etiqueta" :sortActual="$sort" :orderActual="$order"
                            texto="Etiq." padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Planilla" :sortActual="$sort" :orderActual="$order"
                            texto="Planilla" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Obra" :sortActual="$sort" :orderActual="$order"
                            texto="Obra" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Cliente" :sortActual="$sort" :orderActual="$order"
                            texto="Cliente" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Nave" :sortActual="$sort" :orderActual="$order"
                            texto="Nave" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Máquina" :sortActual="$sort" :orderActual="$order"
                            texto="Máq." padding="p-1" />
                        <th>Estado</th>
                        <x-tabla.encabezado-ordenable campo="Peso Estimado (kg)" :sortActual="$sort" :orderActual="$order"
                            texto="Peso" padding="p-1" />
                        <x-tabla.encabezado-ordenable campo="Paquete" :sortActual="$sort" :orderActual="$order"
                            texto="Paq." padding="p-1" />
                        <th>Obs.</th>
                        <th>Traz.</th>
                    </tr>

                    <x-tabla.filtro-row>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="fecha"
                                class="inline-edit-input" placeholder="Fecha...">
                        </th>
                        <th>
                            <select wire:model.live="accion" class="inline-edit-select">
                                <option value="">Todas</option>
                                <option value="INICIO FABRICACIÓN">INICIO FAB.</option>
                                <option value="CAMBIO ESTADO FABRICACIÓN">CAMBIO ESTADO</option>
                                <option value="CREAR PAQUETE">CREAR PAQ.</option>
                                <option value="AÑADIR A PAQUETE">AÑADIR PAQ.</option>
                                <option value="QUITAR DE PAQUETE">QUITAR PAQ.</option>
                                <option value="ELIMINAR PAQUETE">ELIMINAR PAQ.</option>
                            </select>
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="usuario"
                                class="inline-edit-input" placeholder="Usuario...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="usuario2"
                                class="inline-edit-input" placeholder="Usr 2...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="etiqueta"
                                class="inline-edit-input" placeholder="Etiq...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="planilla"
                                class="inline-edit-input" placeholder="Planilla...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="obra"
                                class="inline-edit-input" placeholder="Obra...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="inline-edit-input" placeholder="Cliente...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="nave"
                                class="inline-edit-input" placeholder="Nave...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="maquina"
                                class="inline-edit-input" placeholder="Máq...">
                        </th>
                        <th></th>
                        <th></th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="paquete"
                                class="inline-edit-input" placeholder="Paq...">
                        </th>
                        <th class="text-center align-middle">
                            <button wire:click="limpiarFiltros"
                                class="table-btn bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white"
                                title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>
                        </th>
                        <th></th>
                    </x-tabla.filtro-row>
                </thead>

                <tbody>
                    @forelse ($logs as $log)
                        @php
                            // Colores por tipo de acción
                            $actionColors = [
                                'INICIO FABRICACIÓN' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
                                'CAMBIO ESTADO FABRICACIÓN' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                'CREAR PAQUETE' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                'AÑADIR A PAQUETE' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300',
                                'QUITAR DE PAQUETE' => 'bg-orange-100 dark:bg-orange-900/50 text-orange-800 dark:text-orange-300',
                                'ELIMINAR PAQUETE' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
                            ];
                            $actionClass = $actionColors[$log['Acción']] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';

                            // Verificar si es un log nuevo
                            $isNew = in_array($log['Fecha y Hora'], $newLogIds ?? []);
                        @endphp
                        <x-tabla.row class="text-xs leading-tight {{ $isNew ? 'new-log-row !bg-green-300 dark:!bg-green-900 animate-pulse' : '' }}"
                            data-log-id="{{ $log['Fecha y Hora'] }}">
                            <td class="px-0.5 py-1 text-center border font-mono text-xs">
                                {{ \Carbon\Carbon::parse($log['Fecha y Hora'])->format('d/m H:i') }}</td>
                            <td class="px-0.5 py-1 text-center border">
                                <span class="px-1 py-0.5 rounded text-xs font-medium {{ $actionClass }}">
                                    {{ str_replace(['FABRICACIÓN', 'PAQUETE'], ['FAB.', 'PAQ.'], $log['Acción']) }}
                                </span>
                            </td>
                            <td class="px-0.5 py-1 text-center border text-blue-600 dark:text-blue-400 truncate max-w-[80px]"
                                title="{{ $log['Usuario'] ?? 'Sistema' }}">
                                {{ \Illuminate\Support\Str::limit($log['Usuario'] ?? 'Sistema', 12, '') }}
                            </td>
                            <td class="px-0.5 py-1 text-center border text-green-600 dark:text-green-400 truncate max-w-[80px]"
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
                                    <div class="text-gray-500 dark:text-gray-400 text-xs">
                                        {{ \Illuminate\Support\Str::limit($log['Estado Inicial'] ?? '-', 8, '') }}
                                    </div>
                                    @if (isset($log['Estado Final']) && $log['Estado Final'] !== $log['Estado Inicial'])
                                        <div class="text-gray-400 dark:text-gray-500 text-xs">↓</div>
                                        <div class="text-gray-900 dark:text-gray-100 font-medium text-xs">
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
                                        class="table-btn bg-indigo-600 dark:bg-indigo-700 text-white hover:bg-indigo-700 dark:hover:bg-indigo-600"
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
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-0.5 py-1 text-center border">
                                @if (isset($log['Etiqueta']) && $log['Etiqueta'] !== '-')
                                    <button onclick="filtrarPorEtiqueta('{{ $log['Etiqueta'] }}')"
                                        class="table-btn bg-blue-600 dark:bg-blue-700 text-white hover:bg-blue-700 dark:hover:bg-blue-600"
                                        title="Ver todos los logs de esta etiqueta">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                @endif
                            </td>
                        </x-tabla.row>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No hay registros de producción para mostrar
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Estadísticas y acciones -->
        <div class="mt-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border-l-4 border-blue-500 rounded-r-lg p-3">
            <div class="flex justify-between items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">Registros por página:</span>
                        <select wire:model.live="perPage"
                            class="border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm px-2 py-1 bg-white dark:bg-gray-800 dark:text-gray-200">
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
                                class="text-base font-bold text-blue-800 dark:text-blue-400">{{ number_format($total, 0, ',', '.') }}</span>
                            <span class="text-gray-500 dark:text-gray-400">registros</span>
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
                                class="px-3 py-1 bg-green-600 dark:bg-green-700 text-white rounded hover:bg-green-700 dark:hover:bg-green-600 transition text-sm inline-flex items-center gap-1">
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
