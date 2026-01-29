<x-app-layout>
    <x-slot name="title">Ensamblaje {{ $planilla->codigo_limpio }} - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Ensamblaje {{ $planilla->codigo_limpio }}"
        subtitle="Obra: {{ $planilla->obra->obra ?? '—' }} · Cliente: {{ $planilla->cliente->empresa ?? '—' }}"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>'
    />

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex gap-2">
                <a href="{{ route('planillas.show', $planilla) }}"
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
                    Ver Planilla
                </a>
                <a href="{{ route('planillas.index') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                    Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Resumen --}}
            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                    <div>
                        <p class="text-2xl font-bold text-blue-600">{{ $planilla->entidades->count() }}</p>
                        <p class="text-xs text-gray-500 uppercase">Entidades</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-green-600">{{ $planilla->elementos->count() }}</p>
                        <p class="text-xs text-gray-500 uppercase">Elementos</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-purple-600">
                            {{ number_format($planilla->entidades->sum('peso_total') ?? 0, 0) }} kg
                        </p>
                        <p class="text-xs text-gray-500 uppercase">Peso Total</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-orange-600">
                            {{ $planilla->entidades->sum('total_barras') ?? 0 }}
                        </p>
                        <p class="text-xs text-gray-500 uppercase">Barras</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600">
                            {{ $planilla->entidades->sum('total_estribos') ?? 0 }}
                        </p>
                        <p class="text-xs text-gray-500 uppercase">Estribos</p>
                    </div>
                </div>
            </div>

            {{-- Lista de Entidades --}}
            @if($planilla->entidades->isEmpty())
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                    <p class="text-yellow-700">Esta planilla no tiene información de ensamblaje.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($planilla->entidades as $entidad)
                        <div class="bg-white rounded-lg shadow overflow-hidden" x-data="{ open: false }">
                            {{-- Cabecera de la entidad --}}
                            <div class="p-4 bg-gray-50 border-b cursor-pointer hover:bg-gray-100 transition"
                                 @click="open = !open">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <span class="text-lg font-bold text-blue-600">
                                            {{ $entidad->marca ?: 'Sin marca' }}
                                        </span>
                                        <span class="text-gray-600">{{ $entidad->situacion }}</span>
                                        @if($entidad->cantidad > 1)
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                                                x{{ $entidad->cantidad }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        @if($entidad->longitud_ensamblaje)
                                            <span>L: {{ number_format($entidad->longitud_ensamblaje, 0) }}mm</span>
                                        @endif
                                        @if($entidad->peso_total)
                                            <span>{{ number_format($entidad->peso_total, 2) }}kg</span>
                                        @endif
                                        <svg class="w-5 h-5 transform transition-transform" :class="{ 'rotate-180': open }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </div>
                                @if($entidad->cotas)
                                    <p class="mt-1 text-xs text-gray-400 font-mono">{{ $entidad->cotas }}</p>
                                @endif
                            </div>

                            {{-- Contenido desplegable --}}
                            <div x-show="open" x-collapse>
                                <div class="p-4">
                                    @php
                                        $composicion = $entidad->composicion ?? [];
                                        $barras = $composicion['barras'] ?? [];
                                        $estribos = $composicion['estribos'] ?? [];
                                    @endphp

                                    <div class="grid md:grid-cols-2 gap-6">
                                        {{-- Barras --}}
                                        @if(!empty($barras))
                                            <div>
                                                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                                    <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                                                    Barras ({{ count($barras) }})
                                                </h4>
                                                <div class="space-y-2">
                                                    @foreach($barras as $barra)
                                                        <div class="flex items-center gap-3 p-2 bg-blue-50 rounded text-sm">
                                                            <span class="font-mono text-blue-800 font-bold">
                                                                {{ $barra['cantidad'] ?? 1 }}x
                                                            </span>
                                                            <span class="px-2 py-0.5 bg-blue-200 text-blue-900 rounded text-xs">
                                                                {{ $barra['diametro'] ?? '?' }}
                                                            </span>
                                                            <span class="text-gray-600">
                                                                L={{ $barra['longitud'] ?? '?' }}mm
                                                            </span>
                                                            @if(!empty($barra['peso']))
                                                                <span class="text-gray-400 text-xs">
                                                                    {{ number_format($barra['peso'], 2) }}kg
                                                                </span>
                                                            @endif
                                                            @if(!empty($barra['figura']))
                                                                <span class="text-gray-400 text-xs">
                                                                    Fig: {{ $barra['figura'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Estribos --}}
                                        @if(!empty($estribos))
                                            <div>
                                                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                                    <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                                    Estribos ({{ count($estribos) }})
                                                </h4>
                                                <div class="space-y-2">
                                                    @foreach($estribos as $estribo)
                                                        <div class="flex items-center gap-3 p-2 bg-red-50 rounded text-sm">
                                                            <span class="font-mono text-red-800 font-bold">
                                                                {{ $estribo['cantidad'] ?? 1 }}x
                                                            </span>
                                                            <span class="px-2 py-0.5 bg-red-200 text-red-900 rounded text-xs">
                                                                {{ $estribo['diametro'] ?? '?' }}
                                                            </span>
                                                            <span class="text-gray-600">
                                                                L={{ $estribo['longitud'] ?? '?' }}mm
                                                            </span>
                                                            @if(!empty($estribo['dobleces']))
                                                                <span class="px-2 py-0.5 bg-orange-100 text-orange-800 rounded text-xs">
                                                                    {{ $estribo['dobleces'] }} dobleces
                                                                </span>
                                                            @endif
                                                            @if(!empty($estribo['peso']))
                                                                <span class="text-gray-400 text-xs">
                                                                    {{ number_format($estribo['peso'], 2) }}kg
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Elementos vinculados --}}
                                    @if($entidad->elementos->isNotEmpty())
                                        <div class="mt-4 pt-4 border-t">
                                            <h4 class="font-semibold text-gray-700 mb-2 text-sm">
                                                Elementos vinculados ({{ $entidad->elementos->count() }})
                                            </h4>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($entidad->elementos as $elemento)
                                                    <span class="px-2 py-1 text-xs rounded
                                                        {{ $elemento->estado === 'completado' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                                        {{ $elemento->marca ?: $elemento->codigo }}
                                                        ({{ $elemento->diametro }})
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Distribución si existe --}}
                                    @if(!empty($entidad->distribucion))
                                        <div class="mt-4 pt-4 border-t">
                                            <h4 class="font-semibold text-gray-700 mb-2 text-sm">Distribución</h4>
                                            <pre class="text-xs bg-gray-100 p-2 rounded overflow-x-auto">{{ json_encode($entidad->distribucion, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
