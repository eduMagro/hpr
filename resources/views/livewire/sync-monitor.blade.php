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
        <div class="fixed inset-0 z-[9999] overflow-y-auto" wire:poll.3s="refresh">
            {{-- Overlay - con z-index explícito para cubrir contenido inferior --}}
            <div class="fixed inset-0 z-[9999] bg-black/60 transition-opacity" wire:click="close"></div>

            {{-- Modal Content - z-index mayor que el overlay --}}
            <div class="relative min-h-screen flex items-center justify-center p-4 z-[10000]">
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col isolate">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-4 rounded-t-xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-bold">Monitor de Sincronización FerraWin</h2>
                            {{-- Indicador de entorno --}}
                            @if ($currentTarget === 'production')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-red-500/30 text-red-200 border border-red-400/50">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    PRODUCCIÓN
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-blue-500/30 text-blue-200 border border-blue-400/50">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    LOCAL
                                </span>
                            @endif
                            @if ($isRunning && $isPausing)
                                {{-- Estado: Pausando --}}
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Pausando...
                                </span>
                            @elseif ($isRunning)
                                {{-- Estado: En ejecución --}}
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-300 border border-green-500/30">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                                    </span>
                                    En ejecución
                                </span>
                            @else
                                {{-- Estado: Detenido --}}
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
                    <div class="px-6 py-3 bg-gray-50 rounded-b-xl border-t">
                        {{-- Mensajes flash --}}
                        @if (session()->has('message'))
                            <div class="mb-3 px-3 py-2 bg-green-100 text-green-800 text-sm rounded-lg">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="mb-3 px-3 py-2 bg-red-100 text-red-800 text-sm rounded-lg">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <p class="text-xs text-gray-500">
                                    Auto-actualización cada 3 segundos
                                </p>
                                @if ($ultimaPlanilla && !$isRunning)
                                    <span class="text-xs text-gray-400">
                                        Última: <span class="font-mono font-medium text-gray-600">{{ $ultimaPlanilla }}</span>
                                    </span>
                                @endif
                            </div>

                            <div class="flex gap-2">
                                @if ($currentTarget === 'local')
                                    {{-- CONTROLES SOLO EN LOCAL --}}

                                    {{-- Botón Pausar / Estado pausando --}}
                                    @if ($isRunning)
                                        @if ($isPausing)
                                            {{-- Estado: Pausando (esperando que el proceso se detenga) --}}
                                            <div class="px-3 py-1.5 text-sm font-medium text-white bg-amber-600 rounded-lg flex items-center gap-2 cursor-wait">
                                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span>Pausando...</span>
                                            </div>
                                        @else
                                            {{-- Estado: Normal (puede pausar) --}}
                                            <button wire:click="pausarSync"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-wait"
                                                class="px-3 py-1.5 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Pausar</span>
                                            </button>
                                        @endif
                                    @endif

                                    {{-- Botón Continuar Sincronización --}}
                                    @if ($ultimaPlanilla && !$isRunning)
                                        <button wire:click="continuarSync"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-wait"
                                            class="px-3 py-1.5 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span wire:loading.remove wire:target="continuarSync">Continuar desde {{ $ultimaPlanilla }}</span>
                                            <span wire:loading wire:target="continuarSync">Iniciando...</span>
                                        </button>
                                    @endif

                                    {{-- Botón Iniciar Nueva Sync (Dropdown) --}}
                                    @if (!$isRunning)
                                        <div x-data="{ open: false }" class="relative">
                                            <button @click="open = !open"
                                                class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                Nueva Sync
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak
                                                class="absolute right-0 bottom-full mb-1 w-40 bg-white rounded-lg shadow-lg border py-1 z-10">
                                                @foreach (['2026', '2025', '2024', '2023', '2022'] as $año)
                                                    <button wire:click="seleccionarAño('{{ $año }}')" @click="open = false"
                                                        class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 transition">
                                                        Sincronizar {{ $año }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    {{-- EN PRODUCCIÓN: Mensaje informativo --}}
                                    <span class="px-3 py-1.5 text-xs text-gray-500 bg-gray-100 rounded-lg">
                                        La sincronización se ejecuta desde el servidor local
                                    </span>
                                @endif

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
        </div>
    @endif

    {{-- Modal de confirmación de año - z-index muy alto para aparecer sobre todo --}}
    @if ($showYearConfirm)
        <div class="fixed inset-0 z-[99999] overflow-y-auto" style="z-index: 99999 !important;">
            <div class="fixed inset-0 bg-black/70" style="z-index: 99999 !important;" wire:click="cerrarYearConfirm"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4" style="z-index: 100000 !important;">
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md" style="z-index: 100001 !important;">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl">
                        <h3 class="text-lg font-bold">Sincronizar {{ $selectedYear }}</h3>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        {{-- Selector de destino --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Importar a:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="$set('syncTarget', 'local')"
                                    class="px-4 py-3 rounded-lg border-2 text-center transition {{ $syncTarget === 'local' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-semibold">LOCAL</span>
                                    </div>
                                    <p class="text-xs mt-1 opacity-75">Base de datos local</p>
                                </button>
                                <button wire:click="$set('syncTarget', 'production')"
                                    class="px-4 py-3 rounded-lg border-2 text-center transition {{ $syncTarget === 'production' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-semibold">PRODUCCIÓN</span>
                                    </div>
                                    <p class="text-xs mt-1 opacity-75">app.hierrospacoreyes.es</p>
                                </button>
                            </div>
                        </div>

                        {{-- Estadísticas del año --}}
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600">Planillas importadas ({{ $syncTarget === 'production' ? 'prod' : 'local' }}):</span>
                                <span class="font-bold text-lg {{ $yearPlanillasCount > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                                    {{ number_format($yearPlanillasCount) }}
                                </span>
                            </div>
                            @if ($yearLastPlanilla)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Última planilla:</span>
                                    <span class="font-mono text-sm font-medium text-gray-800">{{ $yearLastPlanilla }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Opciones --}}
                        <div class="space-y-3">
                            @if ($yearPlanillasCount > 0 && $yearLastPlanilla)
                                {{-- Opción: Continuar --}}
                                <button wire:click="confirmarSyncContinuar"
                                    class="w-full px-4 py-3 bg-emerald-50 hover:bg-emerald-100 border-2 border-emerald-200 rounded-lg text-left transition group">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-emerald-500 rounded-lg text-white group-hover:bg-emerald-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-emerald-800">Continuar donde se quedó</p>
                                            <p class="text-xs text-emerald-600">Desde {{ $yearLastPlanilla }} hacia atrás</p>
                                        </div>
                                    </div>
                                </button>
                            @endif

                            {{-- Opción: Desde cero --}}
                            <button wire:click="confirmarSyncCompleta"
                                class="w-full px-4 py-3 bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 rounded-lg text-left transition group">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-500 rounded-lg text-white group-hover:bg-blue-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-blue-800">Empezar desde cero</p>
                                        <p class="text-xs text-blue-600">
                                            @if ($yearPlanillasCount > 0)
                                                Re-procesa todo (actualiza existentes)
                                            @else
                                                Importar todas las planillas del año
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-3 bg-gray-50 rounded-b-xl border-t">
                        <button wire:click="cerrarYearConfirm"
                            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
