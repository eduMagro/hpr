<div>
    {{-- Botón para abrir el modal --}}
    <button type="button" wire:click="open"
        class="px-4 py-2 rounded bg-gradient-to-tr from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold shadow-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Sync FerraWin
        @if ($isRunning)
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
        @endif
    </button>

    {{-- Modal --}}
    @if ($isOpen)
        <div class="fixed inset-0 z-[70] overflow-y-auto" wire:poll.3s="refresh">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="close"></div>

            {{-- Modal Content --}}
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-4 rounded-t-xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-bold">Monitor de Sincronización FerraWin</h2>
                            @if ($isRunning)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-300 border border-green-500/30">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                                    </span>
                                    En ejecución
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-300 border border-yellow-500/30">
                                    <span class="h-2 w-2 rounded-full bg-yellow-400"></span>
                                    Pausado/Detenido
                                </span>
                            @endif
                        </div>
                        <button wire:click="close" class="text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Stats Grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 bg-gray-50 border-b">
                        <div class="bg-white rounded-lg p-3 shadow-sm border">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Progreso</p>
                            <p class="text-xl font-bold text-gray-900">{{ $currentProgress }}</p>
                            <p class="text-xs text-gray-400">Año: {{ $currentYear }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm border">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Planillas en DB</p>
                            <p class="text-xl font-bold text-blue-600">{{ number_format($totalPlanillas) }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm border">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Elementos en DB</p>
                            <p class="text-xl font-bold text-indigo-600">{{ number_format($totalElementos) }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm border">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Batches</p>
                            <p class="text-sm">
                                <span class="text-green-600 font-bold">{{ $batchesOk }} OK</span>
                                <span class="text-gray-400 mx-1">/</span>
                                <span class="text-red-600 font-bold">{{ $batchesError }} Error</span>
                            </p>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <div class="flex-1 overflow-hidden flex flex-col min-h-0">
                        <div class="px-4 py-2 bg-gray-100 border-b flex items-center justify-between">
                            <div class="flex gap-1">
                                <button wire:click="setTab('logs')"
                                    class="px-3 py-1.5 text-sm font-medium rounded-lg transition {{ $activeTab === 'logs' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-200' }}">
                                    Logs
                                </button>
                                <button wire:click="setTab('errors')"
                                    class="px-3 py-1.5 text-sm font-medium rounded-lg transition flex items-center gap-1.5 {{ $activeTab === 'errors' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-200' }}">
                                    Errores
                                    @if (count($errors) > 0)
                                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full {{ $activeTab === 'errors' ? 'bg-white text-red-600' : 'bg-red-100 text-red-600' }}">
                                            {{ count($errors) }}
                                        </span>
                                    @endif
                                </button>
                            </div>
                            <span class="text-xs text-gray-500">Última actualización: {{ $lastUpdate }}</span>
                        </div>

                        {{-- Tab: Logs --}}
                        @if ($activeTab === 'logs')
                            <div class="flex-1 overflow-y-auto bg-gray-900 p-4 font-mono text-xs" id="sync-logs" style="max-height: 400px;">
                                @foreach ($logs as $log)
                                    @php
                                        $class = 'text-gray-300';
                                        if (str_contains($log, 'ERROR')) $class = 'text-red-400';
                                        elseif (str_contains($log, 'WARNING')) $class = 'text-yellow-400';
                                        elseif (str_contains($log, 'Batch OK')) $class = 'text-green-400';
                                        elseif (str_contains($log, 'INFO')) $class = 'text-blue-300';
                                    @endphp
                                    <div class="{{ $class }} leading-relaxed whitespace-pre-wrap break-all">{{ $log }}</div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Tab: Errores --}}
                        @if ($activeTab === 'errors')
                            <div class="flex-1 overflow-y-auto bg-gray-50 p-4" style="max-height: 400px;">
                                @forelse ($errors as $error)
                                    <div class="mb-3 bg-white rounded-lg border border-red-200 shadow-sm overflow-hidden">
                                        <div class="px-4 py-2 bg-red-50 border-b border-red-100 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    @if($error['tipo'] === 'Timeout') bg-yellow-100 text-yellow-800
                                                    @elseif($error['tipo'] === 'Autenticación') bg-purple-100 text-purple-800
                                                    @else bg-red-100 text-red-800 @endif">
                                                    {{ $error['tipo'] }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ $error['timestamp'] }}</span>
                                            </div>
                                            @if ($error['planilla_cerca'] !== '-')
                                                <span class="text-xs text-gray-500">
                                                    Cerca de: <span class="font-mono font-medium">{{ $error['planilla_cerca'] }}</span>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="px-4 py-3">
                                            <p class="text-sm text-gray-800">{{ $error['descripcion'] }}</p>
                                        </div>
                                        <details class="border-t border-gray-100">
                                            <summary class="px-4 py-2 text-xs text-gray-500 cursor-pointer hover:bg-gray-50">
                                                Ver log completo
                                            </summary>
                                            <div class="px-4 py-2 bg-gray-900 text-xs font-mono text-red-400 whitespace-pre-wrap break-all">
                                                {{ $error['raw'] }}
                                            </div>
                                        </details>
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-gray-500">
                                        <svg class="w-12 h-12 mx-auto text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="font-medium">Sin errores</p>
                                        <p class="text-sm">Todo está funcionando correctamente</p>
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-3 bg-gray-50 rounded-b-xl border-t flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            Auto-actualización cada 3 segundos
                        </p>
                        <div class="flex gap-2">
                            <button wire:click="refresh"
                                class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Actualizar
                            </button>
                            <button wire:click="close"
                                class="px-3 py-1.5 text-sm font-medium text-white bg-gray-800 rounded-lg hover:bg-gray-700 transition">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
