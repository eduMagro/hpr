<x-app-layout>
    <x-slot name="title">Detalle Incidencia #{{ $incidencia->id }} - {{ config('app.name') }}</x-slot>

    <div class="px-4 py-6 max-w-7xl mx-auto">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('incidencias.index') }}"
                class="hover:text-blue-600 transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver a Incidencias
            </a>
            <span class="text-gray-300">/</span>
            <span>#INC-{{ str_pad($incidencia->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Left Column: Machine Info & History --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- Machine Profile --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="w-16 h-16 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 text-2xl border border-gray-200">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 leading-tight">
                                {{ $incidencia->maquina->nombre ?? 'N/A' }}
                            </h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span
                                    class="inline-flex items-center gap-1 {{ $incidencia->maquina->estado == 'activa' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }} border text-xs font-bold px-2 py-0.5 rounded-full">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full {{ $incidencia->maquina->estado == 'activa' ? 'bg-green-500' : 'bg-red-500 animate-pulse' }}"></span>
                                    {{ strtoupper($incidencia->maquina->estado) }}
                                </span>
                                <span class="text-xs text-gray-500 font-mono">{{ $incidencia->maquina->codigo }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm mt-4 pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <div class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">Nave</div>
                            <div class="font-bold text-gray-800">
                                {{ $incidencia->maquina->obra->obra ?? 'Sin Asignar' }}
                            </div>
                        </div>
                        <div class="text-center border-l border-gray-100">
                            <div class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">Modelo</div>
                            <div class="font-bold text-gray-800">
                                {{ $incidencia->maquina->tipo ?? 'Desconocido' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm relative">
                    <h3 class="font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Historial Reciente
                    </h3>

                    <div class="space-y-8 relative pl-5 border-l-2 border-gray-300 ml-3">
                        @foreach ($historial as $h)
                            <div class="relative group">
                                <div
                                    class="absolute -left-[26px] top-0 w-8 h-8 rounded-full bg-white outline outline-4 outline-white border-2 {{ $h->estado == 'resuelta' ? 'border-green-500 text-green-500' : 'border-red-500 text-red-500' }} flex items-center justify-center shadow-sm">
                                    @if ($h->estado == 'resuelta')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="pl-4">
                                    <div
                                        class="text-[10px] {{ $h->estado == 'resuelta' ? 'text-green-600' : 'text-red-500' }} font-bold uppercase tracking-wider mb-0.5">
                                        {{ ucfirst($h->estado) }}
                                    </div>
                                    <a href="{{ route('incidencias.show', $h->id) }}"
                                        class="font-semibold text-gray-800 hover:text-blue-600 transition-colors block leading-tight">
                                        {{ $h->titulo }}
                                    </a>
                                    <p class="text-xs text-gray-400 mt-1">{{ $h->fecha_reporte->format('d M Y') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- Right Column: Active Incident Detail --}}
            <div class="lg:col-span-2">

                <div class="bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden flex flex-col h-full">

                    {{-- Toolbar --}}
                    <div
                        class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h2 class="text-xl font-bold text-gray-900">
                                    #INC-{{ str_pad($incidencia->id, 4, '0', STR_PAD_LEFT) }}</h2>
                                @if ($incidencia->estado == 'resuelta')
                                    <span
                                        class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full border border-green-200">RESUELTA</span>
                                @else
                                    <span
                                        class="bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded-full border border-red-200 animate-pulse">ABIERTA</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 flex items-center gap-1">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Reportado {{ $incidencia->fecha_reporte->diffForHumans() }} por <strong
                                    class="text-gray-700">{{ $incidencia->user->name ?? 'Usuario' }}</strong>
                            </p>
                        </div>

                        @if ($incidencia->estado != 'resuelta')
                            <div x-data="{ openResolve: false }">
                                <button @click="openResolve = true"
                                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-green-600/20 transition-all flex items-center gap-2 transform hover:-translate-y-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Resolver Incidencia
                                </button>

                                {{-- Resolve Modal --}}
                                <div x-show="openResolve" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                    style="display: none;">
                                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="openResolve = false">
                                    </div>
                                    <div
                                        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden">
                                        <div class="bg-green-600 px-6 py-4 text-white">
                                            <h3 class="font-bold text-lg">Resolver Incidencia</h3>
                                            <p class="text-green-100 text-sm opacity-90">Indica cómo se solucionó el
                                                problema.</p>
                                        </div>
                                        <form action="{{ route('incidencias.resolve', $incidencia->id) }}" method="POST"
                                            class="p-6">
                                            @csrf
                                            @method('PUT')
                                            <div class="mb-4">
                                                <label class="block text-sm font-bold text-gray-700 mb-2">Descripción
                                                    de la solución</label>
                                                <textarea name="resolucion" rows="4"
                                                    class="w-full border-gray-300 rounded-xl focus:ring-green-500 focus:border-green-500 text-sm"
                                                    placeholder="Se cambió la pieza X, se reinició el sistema, etc."
                                                    required></textarea>
                                            </div>

                                            <div class="mb-6">
                                                <label class="block text-sm font-bold text-gray-700 mb-2">Coste de
                                                    Reparación (Opcional)</label>
                                                <div class="relative rounded-xl shadow-sm">
                                                    <div
                                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 sm:text-sm">€</span>
                                                    </div>
                                                    <input type="number" name="coste" step="0.01" min="0" placeholder="0.00"
                                                        class="focus:ring-green-500 focus:border-green-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-xl">
                                                </div>
                                                <p class="mt-1.5 text-xs text-gray-500 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Si se indica un coste, se generará un registro en Gastos
                                                    automáticamente.
                                                </p>
                                            </div>

                                            <div class="flex justify-end gap-3">
                                                <button type="button" @click="openResolve = false"
                                                    class="px-4 py-2 text-gray-500 hover:text-gray-700 font-medium">Cancelar</button>
                                                <button type="submit"
                                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-bold shadow-lg shadow-green-500/30">Confirmar
                                                    Resolución</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-right">
                                <div class="text-xs text-uppercase text-gray-400 font-bold mb-1">RESUELTO POR</div>
                                <div class="flex items-center gap-2 justify-end">
                                    <div
                                        class="w-6 h-6 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold border border-green-200">
                                        {{ substr($incidencia->resolver->name ?? '?', 0, 1) }}
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-gray-800">{{ $incidencia->resolver->name ?? 'Usuario' }}</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $incidencia->fecha_resolucion->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="p-6 flex-1 overflow-y-auto">

                        {{-- Description --}}
                        <div class="mb-8">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Descripción del
                                Problema</h3>
                            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                                <h4 class="font-bold text-lg text-gray-900 mb-2">{{ $incidencia->titulo }}</h4>
                                <p class="text-gray-600 leading-relaxed text-base">{{ $incidencia->descripcion }}</p>
                            </div>
                        </div>

                        {{-- Resolution if resolved --}}
                        {{-- Resolution if resolved --}}
                        @if ($incidencia->estado == 'resuelta')
                            <div class="mb-8" x-data="{ openEdit: false }">
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Solución
                                        Aplicada</h3>
                                    <button @click="openEdit = true"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-bold hover:underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                        Editar
                                    </button>
                                </div>
                                <div class="bg-green-50 border border-green-200 rounded-xl p-5 shadow-sm">
                                    <p class="text-green-800 leading-relaxed">{{ $incidencia->resolucion }}</p>

                                    @if($incidencia->coste > 0)
                                        <div class="mt-4 pt-4 border-t border-green-200 flex items-center gap-2">
                                            <span class="text-xs font-bold uppercase text-green-700/70">Coste de
                                                reparación:</span>
                                            <span
                                                class="text-lg font-bold text-green-900">{{ number_format($incidencia->coste, 2, ',', '.') }}
                                                €</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Edit Modal --}}
                                <div x-show="openEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                    style="display: none;">
                                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="openEdit = false"></div>
                                    <div
                                        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden">
                                        <div class="bg-blue-600 px-6 py-4 text-white">
                                            <h3 class="font-bold text-lg">Editar Resolución</h3>
                                            <p class="text-blue-100 text-sm opacity-90">Modificar detalles o coste.</p>
                                        </div>
                                        <form action="{{ route('incidencias.update-resolution', $incidencia->id) }}"
                                            method="POST" class="p-6">
                                            @csrf
                                            @method('PUT')

                                            <div class="mb-4">
                                                <label class="block text-sm font-bold text-gray-700 mb-2">Descripción de la
                                                    solución</label>
                                                <textarea name="resolucion" rows="4"
                                                    class="w-full border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    required>{{ $incidencia->resolucion }}</textarea>
                                            </div>

                                            <div class="mb-6">
                                                <label class="block text-sm font-bold text-gray-700 mb-2">Coste de
                                                    Reparación (Opcional)</label>
                                                <div class="relative rounded-xl shadow-sm">
                                                    <div
                                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 sm:text-sm">€</span>
                                                    </div>
                                                    <input type="number" name="coste" step="0.01" min="0" placeholder="0.00"
                                                        value="{{ $incidencia->coste }}"
                                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-xl">
                                                </div>
                                                <p class="mt-1.5 text-xs text-gray-500 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Se actualizará el registro de Gastos vinculado si procede.
                                                </p>
                                            </div>

                                            <div class="flex justify-end gap-3">
                                                <button type="button" @click="openEdit = false"
                                                    class="px-4 py-2 text-gray-500 hover:text-gray-700 font-medium">Cancelar</button>
                                                <button type="submit"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold shadow-lg shadow-blue-500/30">Guardar
                                                    Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Photos Grid --}}
                        @if ($incidencia->fotos && count($incidencia->fotos) > 0)
                            <div class="mb-8">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Evidencia
                                    Fotográfica</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach ($incidencia->fotos as $foto)
                                        <div class="group relative aspect-video bg-gray-100 rounded-xl overflow-hidden border border-gray-200 shadow-sm cursor-zoom-in"
                                            onclick="window.open('{{ asset($foto) }}', '_blank')">
                                            <img src="{{ asset($foto) }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                alt="Evidencia">
                                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($incidencia->estado != 'resuelta')
                            <div class="mb-8">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Evidencia
                                </h3>
                                <div
                                    class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-8 text-center text-gray-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm">No se adjuntaron fotos</p>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>