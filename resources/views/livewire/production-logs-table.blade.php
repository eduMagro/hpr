<div wire:poll.5s id="production-logs-component">
    <div class="w-full px-6 py-4">

        <!-- Selector de archivo de logs -->
        <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 border-l-4 border-indigo-500 rounded-r-lg p-3 mb-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-semibold text-indigo-800">📅 Archivo de Logs:</label>
                <select wire:model.live="selectedFile" class="border-indigo-300 rounded-md shadow-sm text-sm px-3 py-1.5 bg-white text-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                    @foreach($logFiles as $file)
                        <option value="{{ basename($file['path']) }}">
                            {{ basename($file['path']) }} wire:navigate
                            ({{ \Carbon\Carbon::createFromTimestamp($file['modified'])->format('d/m/Y') }})
                            - {{ number_format($file['size'] / 1024, 2) }} KB
                        </option>
                    @endforeach
                </select>
                <span class="text-xs text-indigo-600 font-medium">
                    Total de archivos: {{ count($logFiles) }} wire:navigate
                </span>
            </div>
        </div>

        <x-tabla.filtros-aplicados :filtros="$this->getFiltrosActivos()" />

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[2500px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-4">
                    <tr class="text-center text-xs uppercase">
                        <x-tabla.encabezado-ordenable campo="Fecha y Hora" :sortActual="$sort" :orderActual="$order" texto="Fecha y Hora" />
                        <x-tabla.encabezado-ordenable campo="Acción" :sortActual="$sort" :orderActual="$order" texto="Acción" />
                        <x-tabla.encabezado-ordenable campo="Usuario" :sortActual="$sort" :orderActual="$order" texto="Usuario" />
                        <x-tabla.encabezado-ordenable campo="Usuario 2" :sortActual="$sort" :orderActual="$order" texto="Usuario 2" />
                        <x-tabla.encabezado-ordenable campo="Etiqueta" :sortActual="$sort" :orderActual="$order" texto="Etiqueta" />
                        <x-tabla.encabezado-ordenable campo="Planilla" :sortActual="$sort" :orderActual="$order" texto="Planilla" />
                        <x-tabla.encabezado-ordenable campo="Obra" :sortActual="$sort" :orderActual="$order" texto="Obra" />
                        <x-tabla.encabezado-ordenable campo="Cliente" :sortActual="$sort" :orderActual="$order" texto="Cliente" />
                        <x-tabla.encabezado-ordenable campo="Nave" :sortActual="$sort" :orderActual="$order" texto="Nave" />
                        <x-tabla.encabezado-ordenable campo="Máquina" :sortActual="$sort" :orderActual="$order" texto="Máquina" />
                        <th class="p-2 border">Estado</th>
                        <x-tabla.encabezado-ordenable campo="Peso Estimado (kg)" :sortActual="$sort" :orderActual="$order" texto="Peso (kg)" />
                        <x-tabla.encabezado-ordenable campo="Paquete" :sortActual="$sort" :orderActual="$order" texto="Paquete" />
                        <th class="p-2 border">Observaciones</th>
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
                                'ELIMINAR PAQUETE' => 'bg-red-100 text-red-800'
                            ];
                            $actionClass = $actionColors[$log['Acción']] ?? 'bg-gray-100 text-gray-800';

                            // Verificar si es un log nuevo
                            $isNew = in_array($log['Fecha y Hora'], $newLogIds ?? []);
                        @endphp
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 text-xs leading-none transition-colors {{ $isNew ? 'new-log-row bg-green-300 animate-pulse' : '' }}"
                            data-log-id="{{ $log['Fecha y Hora'] }}">
                            <td class="p-2 text-center border font-mono">{{ $log['Fecha y Hora'] }}</td>
                            <td class="p-2 text-center border">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $actionClass }}">
                                    {{ $log['Acción'] }}
                                </span>
                            </td>
                            <td class="p-2 text-center border font-semibold text-blue-600">{{ $log['Usuario'] ?? 'Sistema' }}</td>
                            <td class="p-2 text-center border font-semibold text-green-600">{{ $log['Usuario 2'] ?? '-' }}</td>
                            <td class="p-2 text-center border font-semibold">{{ $log['Etiqueta'] ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $log['Planilla'] ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $log['Obra'] ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $log['Cliente'] ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $log['Nave'] ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $log['Máquina'] ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                <div class="text-xs">
                                    <div class="text-gray-500">{{ $log['Estado Inicial'] ?? '-' }}</div>
                                    @if(isset($log['Estado Final']) && $log['Estado Final'] !== $log['Estado Inicial'])
                                        <div class="text-gray-400">↓</div>
                                        <div class="text-gray-900 font-medium">{{ $log['Estado Final'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="p-2 text-right border">{{ $log['Peso Estimado (kg)'] ?? '-' }}</td>
                            <td class="p-2 text-center border font-mono">{{ $log['Paquete'] ?? '-' }}</td>
                            <td class="p-2 text-xs border max-w-xs truncate" title="{{ $log['Observaciones'] ?? '' }}">
                                {{ $log['Observaciones'] ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-8 text-gray-500">
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
                            <span class="text-base font-bold text-blue-800">{{ number_format($total, 0, ',', '.') }}</span>
                            <span class="text-gray-500">registros</span>
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if(count($logFiles) > 0)
                        @php
                            $currentFile = collect($logFiles)->firstWhere(function($file) {
                                return basename($file['path']) === ($this->selectedFile ?? basename(\App\Services\ProductionLogger::getCurrentLogPath()));
                            });
                        @endphp
                        @if($currentFile)
                            <a href="{{ route('production.logs.download', basename($currentFile['path'])) }}" wire:navigate
                               class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm inline-flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
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
            {{ $logs->links('vendor.livewire.tailwind') }} wire:navigate
        </div>
    </div>

    @push('scripts')
    <script>
        // Remover señalización de logs nuevos después de 25 segundos
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
    @endpush
</div>
