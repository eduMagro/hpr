<div wire:poll.5s="checkSyncStatus">
    {{-- Botón para abrir el modal --}}
    <button type="button" wire:click="open"
        class="px-4 py-2 rounded bg-gradient-to-tr from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold shadow-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Sync FerraWin
        @if ($isRunning)
            <span class="relative flex shrink-0 h-2 w-2">
                <span class="animate-ping absolute inset-0 rounded-full bg-green-400 opacity-75"></span>
                <span class="relative rounded-full h-2 w-2 bg-green-500"></span>
            </span>
        @endif
    </button>

    {{-- Modal --}}
    @if ($isOpen)
        <div class="fixed inset-0 z-[9999] overflow-y-auto" @if($isRunning || ($currentTarget === 'production' && $remoteStatus === 'running')) wire:poll.5s="refresh" @endif>
            {{-- Overlay - con z-index explícito para cubrir contenido inferior --}}
            <div class="fixed inset-0 z-[9999] bg-black/60 transition-opacity" wire:click="close"></div>

            {{-- Modal Content - z-index mayor que el overlay --}}
            <div class="relative min-h-screen flex items-center justify-center p-4 z-[10000]">
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col isolate">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-4 rounded-t-xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-bold">Monitor de Sincronización FerraWin</h2>
                            {{-- Indicador de destino de sincronización --}}
                            @php
                                // Si hay sync en ejecución, mostrar el target de esa sync
                                // Si no, mostrar el entorno actual
                                $displayTarget = ($isRunning && $runningTarget) ? $runningTarget : $currentTarget;
                            @endphp
                            @if ($displayTarget === 'production')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-red-500/30 text-red-200 border border-red-400/50">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $isRunning ? '→ PRODUCCIÓN' : 'PRODUCCIÓN' }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-blue-500/30 text-blue-200 border border-blue-400/50">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $isRunning ? '→ LOCAL' : 'LOCAL' }}
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
                                    <span class="relative flex shrink-0 h-2 w-2">
                                        <span class="animate-ping absolute inset-0 rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative rounded-full h-2 w-2 bg-green-400"></span>
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
                        {{-- Estado Remoto (solo en producción) --}}
                        @if ($currentTarget === 'production' && $remoteStatus)
                            <div class="col-span-2 md:col-span-4 mb-2">
                                <div class="rounded-lg p-4 border-2 flex items-center justify-between
                                    @if($remoteStatus === 'running') bg-green-50 border-green-300
                                    @elseif($remoteStatus === 'completed') bg-blue-50 border-blue-300
                                    @elseif($remoteStatus === 'paused') bg-amber-50 border-amber-300
                                    @elseif($remoteStatus === 'error') bg-red-50 border-red-300
                                    @else bg-gray-50 border-gray-300 @endif">
                                    <div class="flex items-center gap-3">
                                        @if($remoteStatus === 'running')
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-500 text-white">
                                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-green-800 text-lg">Sincronizando...</p>
                                                <p class="text-sm text-green-600">{{ $remoteMessage ?: 'Procesando planillas' }}</p>
                                            </div>
                                        @elseif($remoteStatus === 'completed')
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-500 text-white">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-blue-800 text-lg">Finalizado</p>
                                                <p class="text-sm text-blue-600">{{ $remoteMessage ?: 'Sincronización completada' }}</p>
                                            </div>
                                        @elseif($remoteStatus === 'paused')
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-amber-500 text-white">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-amber-800 text-lg">Pausado</p>
                                                <p class="text-sm text-amber-600">{{ $remoteMessage ?: 'Sincronización pausada' }}</p>
                                            </div>
                                        @elseif($remoteStatus === 'error')
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-500 text-white">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-red-800 text-lg">Error</p>
                                                <p class="text-sm text-red-600">{{ $remoteMessage ?: 'Error en sincronización' }}</p>
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-400 text-white">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-700 text-lg">En espera</p>
                                                <p class="text-sm text-gray-500">{{ $remoteMessage ?: 'Esperando sincronización' }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        @if($remoteProgress)
                                            <p class="font-bold text-lg text-gray-800">{{ $remoteProgress }}</p>
                                        @endif
                                        @if($remoteUpdatedAt)
                                            <p class="text-xs text-gray-500">
                                                {{ \Carbon\Carbon::parse($remoteUpdatedAt)->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

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
                            <span class="text-xs text-gray-500">
                                @if($isRunning)
                                    Auto-refresh activo
                                @endif
                                {{ $lastUpdate }}
                            </span>
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
                    <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-b-xl border-t">
                        {{-- Mensajes flash --}}
                        @if (session()->has('message'))
                            <div class="mb-3 px-3 py-2 bg-green-50 text-green-700 text-sm rounded-lg border border-green-200">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="mb-3 px-3 py-2 bg-red-50 text-red-700 text-sm rounded-lg border border-red-200">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            {{-- Info izquierda --}}
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                @if($isRunning)
                                    <span class="flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                        Auto-refresh
                                    </span>
                                @endif
                                @if ($ultimaPlanilla && !$isRunning)
                                    <span class="font-mono bg-gray-200 px-2 py-0.5 rounded">{{ $ultimaPlanilla }}</span>
                                @endif
                            </div>

                            {{-- Botones derecha --}}
                            <div class="flex flex-wrap items-center gap-2">
                                @php
                                    $btnBase = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-150 shadow-sm';
                                    $btnPrimary = $btnBase . ' text-white bg-gray-800 hover:bg-gray-900 active:scale-95';
                                    $btnSecondary = $btnBase . ' text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400 active:scale-95';
                                    $btnDanger = $btnBase . ' text-white bg-gray-800 hover:bg-red-600 active:scale-95';
                                @endphp

                                {{-- Pausar --}}
                                @if ($isRunning)
                                    @if ($isPausing)
                                        <div class="{{ $btnSecondary }} cursor-wait opacity-75">
                                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Pausando...
                                        </div>
                                    @else
                                        <button wire:click="pausarSync" class="{{ $btnSecondary }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6" />
                                            </svg>
                                            Pausar
                                        </button>
                                    @endif
                                @endif

                                {{-- Continuar --}}
                                @if ($currentTarget === 'local' && $ultimaPlanilla && !$isRunning)
                                    <button wire:click="continuarSync" class="{{ $btnPrimary }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        </svg>
                                        <span wire:loading.remove wire:target="continuarSync">Continuar</span>
                                        <span wire:loading wire:target="continuarSync">...</span>
                                    </button>
                                @endif

                                {{-- Sync planilla específica --}}
                                @if (!$isRunning)
                                    <div class="flex items-center gap-1 bg-white rounded-lg border border-gray-300 p-0.5">
                                        <input type="text"
                                            wire:model="codigoPlanillaEspecifica"
                                            wire:keydown.enter="syncPlanillaEspecifica"
                                            placeholder="2026-886"
                                            class="w-20 px-2 py-1 text-xs border-0 bg-transparent focus:ring-0 focus:outline-none"
                                            title="Código de planilla">
                                        <select wire:model="syncTarget"
                                            class="px-1 py-1 text-xs border-0 bg-transparent focus:ring-0 focus:outline-none cursor-pointer {{ $syncTarget === 'production' ? 'text-red-600' : 'text-gray-600' }}">
                                            @if ($canSyncToLocal)
                                                <option value="local">Local</option>
                                            @endif
                                            <option value="production">Prod</option>
                                        </select>
                                        <button wire:click="syncPlanillaEspecifica"
                                            class="p-1.5 rounded-md {{ $syncTarget === 'production' ? 'bg-gray-800 hover:bg-red-600' : 'bg-gray-800 hover:bg-gray-900' }} text-white transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                @endif

                                {{-- Nueva Sync dropdown --}}
                                @if (!$isRunning)
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click="open = !open" class="{{ $btnPrimary }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Nueva Sync
                                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-cloak
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            class="absolute right-0 bottom-full mb-2 w-44 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-10 overflow-hidden">
                                            <button wire:click="seleccionarAño('nuevas')" @click="open = false"
                                                class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                Solo NUEVAS
                                            </button>
                                            <button wire:click="seleccionarAño('todos')" @click="open = false"
                                                class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                                Sincronizar TODO
                                            </button>
                                            <div class="border-t border-gray-100 my-1"></div>
                                            @foreach (['2026', '2025', '2024', '2023', '2022'] as $año)
                                                <button wire:click="seleccionarAño('{{ $año }}')" @click="open = false"
                                                    class="w-full px-3 py-1.5 text-left text-xs text-gray-600 hover:bg-gray-100">
                                                    {{ $año }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Limpiar Logs --}}
                                @if ($currentTarget === 'local' && !$isRunning)
                                    <button wire:click="limpiarLogs" wire:confirm="¿Eliminar TODOS los archivos de log?" class="{{ $btnSecondary }} hover:text-red-600 hover:border-red-300">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Limpiar
                                    </button>
                                @endif

                                {{-- Actualizar --}}
                                <button wire:click="refresh" class="{{ $btnSecondary }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Actualizar
                                </button>

                                {{-- Cerrar --}}
                                <button wire:click="close" class="{{ $btnPrimary }}">
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
                    <div class="bg-gradient-to-r {{ $selectedYear === 'nuevas' ? 'from-emerald-600 to-emerald-700' : 'from-blue-600 to-blue-700' }} text-white px-6 py-4 rounded-t-xl">
                        <h3 class="text-lg font-bold">
                            @if ($selectedYear === 'todos')
                                Sincronización COMPLETA
                            @elseif ($selectedYear === 'nuevas')
                                Solo Planillas NUEVAS
                            @else
                                Sincronizar {{ $selectedYear }}
                            @endif
                        </h3>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        {{-- Selector de destino --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Importar a:</label>
                            @if ($canSyncToLocal)
                                {{-- Programadores: pueden elegir local o producción --}}
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
                            @else
                                {{-- Otros usuarios: solo producción --}}
                                <div class="px-4 py-3 rounded-lg border-2 border-red-500 bg-red-50 text-red-700 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-semibold">PRODUCCIÓN</span>
                                    </div>
                                    <p class="text-xs mt-1 opacity-75">app.hierrospacoreyes.es</p>
                                </div>
                            @endif
                        </div>

                        {{-- Estadísticas --}}
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600">
                                    @if ($selectedYear === 'todos' || $selectedYear === 'nuevas')
                                        Total planillas importadas ({{ $syncTarget === 'production' ? 'prod' : 'local' }}):
                                    @else
                                        Planillas importadas ({{ $syncTarget === 'production' ? 'prod' : 'local' }}):
                                    @endif
                                </span>
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
                            @if ($selectedYear === 'todos')
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <p class="text-xs text-amber-600 font-medium">
                                        Se sincronizarán TODAS las planillas de FerraWin
                                    </p>
                                </div>
                            @elseif ($selectedYear === 'nuevas')
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <p class="text-xs text-emerald-600 font-medium">
                                        Solo se sincronizarán planillas que NO existan en destino
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Opciones --}}
                        <div class="space-y-3">
                            @if ($selectedYear !== 'todos' && $selectedYear !== 'nuevas' && $yearPlanillasCount > 0 && $yearLastPlanilla)
                                {{-- Opción: Continuar (solo para años específicos) --}}
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

                            {{-- Opción: Desde cero / Iniciar --}}
                            <button wire:click="confirmarSyncCompleta"
                                class="w-full px-4 py-3 bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 rounded-lg text-left transition group">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-500 rounded-lg text-white group-hover:bg-blue-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div>
                                        @if ($selectedYear === 'todos')
                                            <p class="font-semibold text-blue-800">Iniciar sincronización completa</p>
                                            <p class="text-xs text-blue-600">
                                                Sincroniza todas las planillas de FerraWin
                                            </p>
                                        @elseif ($selectedYear === 'nuevas')
                                            <p class="font-semibold text-blue-800">Sincronizar solo nuevas</p>
                                            <p class="text-xs text-blue-600">
                                                Importa planillas que no existan en destino
                                            </p>
                                        @else
                                            <p class="font-semibold text-blue-800">Empezar desde cero</p>
                                            <p class="text-xs text-blue-600">
                                                @if ($yearPlanillasCount > 0)
                                                    Re-procesa todo (actualiza existentes)
                                                @else
                                                    Importar todas las planillas del año
                                                @endif
                                            </p>
                                        @endif
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
