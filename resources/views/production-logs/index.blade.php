<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Logs de Producción en Tiempo Real
            </h2>
            <div class="flex gap-3 items-center">
                <span class="text-sm text-gray-600" id="last-update">
                    Última actualización: {{ now()->format('H:i:s') }}
                </span>
                <button id="toggle-auto-refresh" class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition">
                    ⏸ Pausar
                </button>
                <span class="text-xs font-medium text-green-600" id="stat-refresh">Auto-Refresh: ON</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <!-- Tabla Livewire con auto-refresh -->
            @livewire('production-logs-table')
        </div>
    </div>

    <!-- Modal de Observaciones - FUERA del componente Livewire -->
    <div id="modal-observaciones" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Fondo oscuro con opacidad -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="cerrarModalObservaciones()"></div>

            <!-- Centrar el modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Panel del modal - Más estrecho y más alto -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full" style="max-height: 90vh;">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4" style="max-height: calc(90vh - 80px); overflow-y: auto;">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Observaciones del Log
                            </h3>
                            <div class="mt-2">
                                <div class="mb-3 text-sm text-gray-600">
                                    <span class="font-semibold">Etiqueta:</span> <span id="modal-etiqueta"></span> |
                                    <span class="font-semibold">Acción:</span> <span id="modal-accion"></span>
                                </div>
                                <div class="mt-2 bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <pre id="modal-observaciones-contenido" class="text-sm text-gray-700 whitespace-pre-wrap font-mono leading-relaxed max-h-[70vh] overflow-y-auto"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="cerrarModalObservaciones()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script data-navigate-reload>
        (() => {
            if (window.productionLogsInit) return;
            window.productionLogsInit = true;

            let autoRefreshActive = true;

            document.addEventListener('DOMContentLoaded', function() {
                // Toggle auto-refresh
                function toggleAutoRefresh() {
                    const button = document.getElementById('toggle-auto-refresh');
                    const statusEl = document.getElementById('stat-refresh');
                    const component = document.getElementById('production-logs-component');

                    if (autoRefreshActive) {
                        // Pausar
                        autoRefreshActive = false;
                        button.textContent = '▶ Reanudar';
                        button.classList.remove('bg-green-600', 'hover:bg-green-700');
                        button.classList.add('bg-gray-600', 'hover:bg-gray-700');
                        statusEl.textContent = 'Auto-Refresh: OFF';
                        statusEl.classList.remove('text-green-600');
                        statusEl.classList.add('text-gray-600');

                        // Detener el polling de Livewire
                        if (component) {
                            component.removeAttribute('wire:poll.5s');
                        }
                    } else {
                        // Reanudar
                        autoRefreshActive = true;
                        button.textContent = '⏸ Pausar';
                        button.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                        button.classList.add('bg-green-600', 'hover:bg-green-700');
                        statusEl.textContent = 'Auto-Refresh: ON';
                        statusEl.classList.remove('text-gray-600');
                        statusEl.classList.add('text-green-600');

                        // Reanudar el polling de Livewire
                        if (component) {
                            component.setAttribute('wire:poll.5s', '');
                            // Forzar una actualización inmediata
                            Livewire.all()[0]?.call('$refresh');
                        }
                    }
                }

                // Event listener
                const toggleButton = document.getElementById('toggle-auto-refresh');
                if (toggleButton) {
                    toggleButton.addEventListener('click', toggleAutoRefresh);
                }

                // Actualizar timestamp cada 5 segundos
                setInterval(() => {
                    if (autoRefreshActive) {
                        const now = new Date();
                        const timeStr = now.toLocaleTimeString('es-ES');
                        const updateEl = document.getElementById('last-update');
                        if (updateEl) {
                            updateEl.textContent = `Última actualización: ${timeStr}`;
                        }
                    }
                }, 5000);
            });
        })();
    </script>
    @endpush
</x-app-layout>
