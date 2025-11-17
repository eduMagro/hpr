<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Logs de Producción en Tiempo Real
            </h2>
            <div class="flex gap-3 items-center">
                <span class="text-sm text-gray-600" id="last-update">
                    Última actualización: {{ now()->format('H:i:s') }} wire:navigate
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

    @push('scripts')
    <script>
        let autoRefreshActive = true;
        let pollInterval = null;

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
    </script>
    @endpush
</x-app-layout>
