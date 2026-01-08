<x-app-layout>
    <x-slot name="title">Funciones - {{ config('app.name') }}</x-slot>

    <div class="px-4 py-6 max-w-[1920px] mx-auto" x-data="solicitudesApp()" x-init="initPage()">

        <!-- Header con gradiente -->
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 p-6 rounded-2xl bg-gradient-to-tr from-gray-800 to-gray-900 shadow-lg">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white shadow-inner">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Funciones</h1>
                    <p class="text-white/70 text-sm">Panel de control de tareas</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button @click="openCreateModal()"
                    class="bg-white hover:bg-gray-50 text-indigo-600 px-5 py-2.5 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 font-semibold text-sm flex items-center gap-2 hover:scale-105 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Función
                </button>
            </div>
        </div>

        <!-- Notion-like Tabs -->
        <div class="flex items-center gap-6 mb-4 text-sm border-b border-gray-200 pb-1 overflow-x-auto">
            <button @click="boardView = 'table'"
                :class="boardView === 'table' ? 'text-gray-900 border-b-2 border-gray-900 font-medium' :
                    'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'"
                class="flex items-center gap-2 px-2 py-2 whitespace-nowrap transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Vista Tabla
            </button>

            <button @click="boardView = 'estado'; $nextTick(() => syncKanbanCounts())"
                :class="boardView === 'estado' ? 'text-gray-900 border-b-2 border-gray-900 font-medium' :
                    'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'"
                class="flex items-center gap-2 px-2 py-2 whitespace-nowrap transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z" />
                </svg>
                Por estado
            </button>
        </div>

        <!-- Excel-like Table Container -->
        <div x-show="boardView === 'table'"
            class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden select-none transition-shadow hover:shadow-2xl">
            <div class="overflow-x-auto pb-24" style="min-height: 400px;">
                <table class="w-full border-collapse text-sm table-fixed hpr-solicitudes-table">
                    <colgroup>
                        <col data-column-key="id">
                        <col data-column-key="titulo">
                        <col data-column-key="comentario">
                        <col data-column-key="estado">
                        <col data-column-key="prioridad">
                        <col data-column-key="asignado_a">
                        <col data-column-key="fecha">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <!-- ID Column -->
                            <th data-column-key="id"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-16 border-r border-gray-200 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center gap-1">
                                        <span class="resizable-column-label uppercase text-[10px]">#</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>

                            <!-- Title Column -->
                            <th data-column-key="titulo"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 min-w-[20px] border-r border-gray-200 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center gap-1">
                                        <svg class="w-5 h-5 min-w-5 min-h-5 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span class="resizable-column-label uppercase text-[10px]">Nombre de la
                                            función</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>

                            <!-- Comentario -->
                            <th data-column-key="comentario"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-64 border-r border-gray-200 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center gap-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                        <span class="resizable-column-label uppercase text-[10px]">Comentario</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>

                            <!-- Estado -->
                            <th data-column-key="estado"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-40 border-r border-gray-200 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center gap-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="resizable-column-label uppercase text-[10px]">Estado</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>

                            <!-- Prioridad -->
                            <th data-column-key="prioridad"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-32 border-r border-gray-200 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center gap-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="resizable-column-label uppercase text-[10px]">Prioridad</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>

                            <!-- Asignado -->
                            <th data-column-key="asignado_a"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-48 border-r border-gray-200 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center gap-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="resizable-column-label uppercase text-[10px]">Asignado a</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>

                            <!-- Fecha -->
                            <th data-column-key="fecha"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-48 border-r border-gray-200 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center gap-1">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="resizable-column-label uppercase text-[10px]">Fecha</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($solicitudes as $solicitud)
                            <tr
                                class="group hover:bg-gradient-to-r hover:from-indigo-50/50 hover:to-purple-50/50 transition-all duration-200 relative">
                                <!-- ID -->
                                <td
                                    class="px-2 py-1 border-r border-gray-200 text-gray-400 text-xs font-mono w-16 text-center">
                                    {{ $solicitud->id }}
                                </td>

                                <!-- Titulo & Open Button -->
                                <td class="px-2 py-1 border-r border-gray-200 relative group/cell">
                                    <div class="relative flex items-center h-8">
                                        <input type="text" value="{{ $solicitud->titulo }}"
                                            @change="updateField({{ $solicitud->id }}, 'titulo', $event.target.value)"
                                            class="w-full h-full bg-transparent border-none focus:ring-1 focus:ring-blue-500 rounded text-sm text-gray-900 font-medium placeholder-gray-400 px-1"
                                            placeholder="Sin título">

                                        <button @click='openViewModal(@json($solicitud, JSON_HEX_APOS))'
                                            class="opacity-0 group-hover:opacity-100 flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 hover:text-gray-700 rounded shadow-sm transition-all absolute right-2 top-1/2 -translate-y-1/2 z-10 tracking-wide uppercase">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Open
                                        </button>
                                    </div>
                                </td>

                                <!-- Comentario -->
                                <td class="px-2 py-1 border-r border-gray-200">
                                    <input type="text" value="{{ $solicitud->comentario }}"
                                        @change="updateField({{ $solicitud->id }}, 'comentario', $event.target.value)"
                                        class="w-full h-8 px-2 bg-transparent border-none focus:ring-1 focus:ring-blue-500 rounded text-sm text-gray-600 placeholder-gray-300 truncate"
                                        placeholder="Vacío">
                                </td>

                                <!-- Estado -->
                                <td class="px-2 py-1 border-r border-gray-200 has-dropdown">
                                    <div x-data="{
                                        open: false,
                                        selected: @js($solicitud->estado),
                                        getColor(val) {
                                            const v = (val || '').toLowerCase();
                                            if (v === 'nueva') return { dot: 'bg-gray-400', bg: 'bg-gray-100', text: 'text-gray-700' };
                                            if (v === 'lanzada') return { dot: 'bg-blue-500', bg: 'bg-blue-100', text: 'text-blue-700' };
                                            if (v === 'en progreso') return { dot: 'bg-purple-500', bg: 'bg-purple-100', text: 'text-purple-700' };
                                            if (v === 'en revisión') return { dot: 'bg-amber-500', bg: 'bg-amber-100', text: 'text-amber-700' };
                                            if (v === 'merged') return { dot: 'bg-emerald-500', bg: 'bg-emerald-100', text: 'text-emerald-700' };
                                            if (v === 'completada') return { dot: 'bg-green-600', bg: 'bg-green-100', text: 'text-green-700' };
                                            return { dot: 'bg-gray-300', bg: 'bg-gray-50', text: 'text-gray-600' };
                                        },
                                        update(val) {
                                            this.selected = val;
                                            this.open = false;
                                            updateField({{ $solicitud->id }}, 'estado', val);
                                        }
                                    }" class="relative">
                                        <button @click="open = !open" type="button"
                                            class="flex items-center gap-2 h-7 w-full px-2 rounded text-[11px] font-bold transition-all border border-transparent hover:border-gray-200 shadow-sm"
                                            :class="getColor(selected).bg + ' ' + getColor(selected).text">
                                            <span class="w-2 h-2 rounded-full shrink-0"
                                                :class="getColor(selected).dot"></span>
                                            <span class="flex-1 text-left" x-text="selected"></span>
                                            <svg class="w-3 h-3 opacity-40 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            class="absolute z-[9999] mt-1 w-40 bg-white rounded-lg shadow-xl border border-gray-200 py-1 overflow-hidden left-0"
                                            x-cloak style="display: none;">
                                            @foreach ($estados as $est)
                                                <button @click="update('{{ $est }}')" type="button"
                                                    class="flex items-center gap-2 w-full px-3 py-1.5 text-[11px] font-bold hover:bg-gray-50 transition-colors"
                                                    :class="getColor('{{ $est }}').text">
                                                    <span class="w-2 h-2 rounded-full shrink-0"
                                                        :class="getColor('{{ $est }}').dot"></span>
                                                    {{ $est }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>

                                <!-- Prioridad -->
                                <td class="px-2 py-1 border-r border-gray-200 has-dropdown">
                                    <div x-data="{
                                        open: false,
                                        selected: @js($solicitud->prioridad),
                                        getColor(val) {
                                            const v = (val || '').toLowerCase();
                                            if (v === 'alta') return { dot: 'bg-red-500', bg: 'bg-red-100', text: 'text-red-700' };
                                            if (v === 'media') return { dot: 'bg-yellow-500', bg: 'bg-yellow-100', text: 'text-yellow-700' };
                                            if (v === 'baja') return { dot: 'bg-sky-400', bg: 'bg-sky-100', text: 'text-sky-700' };
                                            return { dot: 'bg-gray-300', bg: 'bg-gray-50', text: 'text-gray-600' };
                                        },
                                        update(val) {
                                            this.selected = val;
                                            this.open = false;
                                            updateField({{ $solicitud->id }}, 'prioridad', val);
                                        }
                                    }" class="relative">
                                        <button @click="open = !open" type="button"
                                            class="flex items-center gap-2 h-7 w-full px-2 rounded text-[11px] font-bold transition-all border border-transparent hover:border-gray-200 shadow-sm"
                                            :class="getColor(selected).bg + ' ' + getColor(selected).text">
                                            <span class="w-2 h-2 rounded-full shrink-0"
                                                :class="getColor(selected).dot"></span>
                                            <span class="flex-1 text-left" x-text="selected"></span>
                                            <svg class="w-3 h-3 opacity-40 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            class="absolute z-[9999] mt-1 w-32 bg-white rounded-lg shadow-xl border border-gray-200 py-1 overflow-hidden left-0"
                                            x-cloak style="display: none;">
                                            @foreach ($prioridades as $pri)
                                                <button @click="update('{{ $pri }}')" type="button"
                                                    class="flex items-center gap-2 w-full px-3 py-1.5 text-[11px] font-bold hover:bg-gray-50 transition-colors"
                                                    :class="getColor('{{ $pri }}').text">
                                                    <span class="w-2 h-2 rounded-full shrink-0"
                                                        :class="getColor('{{ $pri }}').dot"></span>
                                                    {{ $pri }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>

                                <!-- Asignado -->
                                <td class="px-2 py-1 border-r border-gray-200">
                                    <div class="flex items-center gap-2 h-full">
                                        @if ($solicitud->asignado)
                                            <div
                                                class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-[9px] font-bold text-indigo-600 shrink-0 shadow-sm border border-indigo-200">
                                                {{ substr($solicitud->asignado->name, 0, 1) }}
                                            </div>
                                        @endif
                                        <select
                                            @change="updateField({{ $solicitud->id }}, 'asignado_a', $event.target.value)"
                                            class="w-full h-7 pl-0 pr-6 py-0 bg-transparent border-none focus:ring-0 rounded text-[11px] font-bold text-gray-600 cursor-pointer text-ellipsis overflow-hidden">
                                            <option value="">Sin asignar</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ $solicitud->asignado_a == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>

                                <!-- Fecha -->
                                <td class="px-2 py-1 border-r border-gray-200 text-xs text-gray-500">
                                    {{ $solicitud->created_at->format('M j') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    No hay solicitudes registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination inside container (footer like) -->
            <div class="px-4 py-2 border-t border-gray-200 bg-gray-50 text-xs text-gray-500">
                {{ $solicitudes->links() }}
            </div>
        </div>

        <!-- Kanban by Estado -->
        <div x-show="boardView === 'estado'" x-cloak class="pb-24">
            <div class="flex gap-4 overflow-x-auto items-start">
                @foreach ($estados as $est)
                    @php($count = $solicitudes->getCollection()->where('estado', $est)->count())
                    <div class="w-80 shrink-0 rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3"
                            :class="statusMeta(@js($est)).bg">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                    :class="statusMeta(@js($est)).dot"></span>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold truncate"
                                        :class="statusMeta(@js($est)).text">
                                        {{ $est }}
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-white/70 border border-gray-200"
                                data-kanban-count-for="{{ $est }}">{{ $count }}</span>
                        </div>

                        <div class="p-3 space-y-2" data-kanban-estado="{{ $est }}">
                            @foreach ($solicitudes as $solicitud)
                                @if ($solicitud->estado === $est)
                                    <div data-kanban-card data-solicitud-id="{{ $solicitud->id }}"
                                        data-estado="{{ $solicitud->estado }}"
                                        class="group rounded-xl border border-gray-200 bg-white hover:shadow-md transition-shadow">
                                        <button type="button" @click='openViewModal(@json($solicitud, JSON_HEX_APOS))'
                                            class="w-full text-left p-3">
                                            <div class="flex items-start gap-2">
                                                <div class="w-2 h-2 rounded-full mt-1.5 shrink-0"
                                                    :class="statusMeta(@js($est)).dot"></div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-sm font-semibold text-gray-900 line-clamp-2">
                                                        {{ $solicitud->titulo }}
                                                    </div>
                                                    @if (!empty($solicitud->comentario))
                                                        <div class="text-xs text-gray-500 mt-1 line-clamp-2">
                                                            {{ $solicitud->comentario }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                @endif
                            @endforeach

                            <button type="button" @click="openCreateModal()"
                                class="w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-600 transition-colors">
                                + Nueva solicitud
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>


        <!-- Create/Edit Modal (Side Drawer) con animaciones -->
        <div x-show="isModalOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-30 overflow-hidden" style="display: none;"
            aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()">
                </div>
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    <div x-show="isModalOpen" x-transition:enter="transform transition ease-out duration-300"
                        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in duration-200"
                        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                        class="pointer-events-auto w-screen max-w-2xl bg-white shadow-2xl flex flex-col h-full rounded-l-2xl">

                        <!-- Header -->
                        <div
                            class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white z-20">
                            <div class="flex items-center gap-2 text-gray-400 hover:text-gray-600 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                </svg>
                                <span class="text-sm">Abierto como página</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <button @click="closeModal()"
                                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md p-1 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto bg-white">
                            <form :action="isEditing ? '/funciones/' + form.id : '{{ route('funciones.store') }}'"
                                method="POST" id="solicitudForm" class="h-full flex flex-col">
                                @csrf
                                <template x-if="isEditing">
                                    <input type="hidden" name="_method" value="PUT">
                                </template>

                                <!-- Notion-like Top Section -->
                                <div class="px-10 pt-10 pb-4">
                                    <!-- Title -->
                                    <textarea name="titulo" x-model="form.titulo" placeholder="Nombre de la función"
                                        class="w-full text-4xl font-bold border-none focus:ring-0 placeholder-gray-300 px-0 mb-6 text-gray-900 leading-tight resize-none overflow-hidden"
                                        rows="1" @input="$el.style.height = ''; $el.style.height = $el.scrollHeight + 'px'"></textarea>

                                    <!-- Properties -->
                                    <div class="space-y-1 mb-8">
                                        <!-- Estado -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div class="w-36 text-gray-500 flex items-center gap-2 text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Estado
                                            </div>
                                            <div class="flex-1">
                                                <select name="estado" x-model="form.estado"
                                                    class="border-none bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded px-2 py-1 cursor-pointer focus:ring-0 w-auto transition-colors">
                                                    @foreach ($estados as $estado)
                                                        <option value="{{ $estado }}">{{ $estado }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Prioridad -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div class="w-36 text-gray-500 flex items-center gap-2 text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                Prioridad
                                            </div>
                                            <div class="flex-1">
                                                <select name="prioridad" x-model="form.prioridad"
                                                    class="border-none bg-transparent hover:bg-gray-100 text-gray-700 text-sm rounded px-2 py-1 cursor-pointer focus:ring-0 w-auto transition-colors">
                                                    @foreach ($prioridades as $prioridad)
                                                        <option value="{{ $prioridad }}">{{ $prioridad }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Asignado -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div class="w-36 text-gray-500 flex items-center gap-2 text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                                Asignado a
                                            </div>
                                            <div class="flex-1">
                                                <select name="asignado_a" x-model="form.asignado_a"
                                                    class="border-none bg-transparent hover:bg-gray-100 text-gray-700 text-sm rounded px-2 py-1 cursor-pointer focus:ring-0 w-auto transition-colors">
                                                    <option value="">Sin asignar</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Comentario (in Modal) -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div class="w-36 text-gray-500 flex items-center gap-2 text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                </svg>
                                                Comentario
                                            </div>
                                            <div class="flex-1">
                                                <input type="text" name="comentario" x-model="form.comentario"
                                                    class="w-full border-none bg-transparent hover:bg-gray-50 focus:bg-white text-gray-700 text-sm rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 placeholder-gray-400"
                                                    placeholder="Añadir un comentario...">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="h-px bg-gray-200 w-full mb-8"></div>
                                </div>

                                <!-- Markdown Editor/Viewer -->
                                <div class="px-10 pb-10 flex-1 flex flex-col">
                                    <div class="flex items-center gap-4 mb-4 border-b border-gray-100 pb-2">
                                        <button type="button" @click="viewMode = 'edit'"
                                            :class="{ 'text-gray-900 font-semibold': viewMode === 'edit', 'text-gray-500 hover:text-gray-700': viewMode !== 'edit' }"
                                            class="pb-2 text-sm transition-colors">Editar</button>
                                        <button type="button" @click="renderMarkdown()"
                                            :class="{ 'text-gray-900 font-semibold': viewMode === 'preview', 'text-gray-500 hover:text-gray-700': viewMode !== 'preview' }"
                                            class="pb-2 text-sm transition-colors">Vista Previa</button>
                                    </div>

                                    <!-- Edit Mode -->
                                    <div x-show="viewMode === 'edit'" class="flex-1 flex flex-col min-h-0">
                                        <textarea name="descripcion" x-model="form.descripcion"
                                            class="w-full flex-1 min-h-[200px] border-0 focus:ring-0 text-gray-800 text-base resize-none p-0 leading-relaxed"
                                            placeholder="Escribe aquí los detalles de la función..."></textarea>
                                    </div>

                                    <!-- Preview Mode -->
                                    <div x-show="viewMode === 'preview'" x-ref="mdPreview"
                                        class="flex-1 min-h-0 overflow-y-auto markdown-preview text-gray-800"
                                        x-html="renderedHtml">
                                    </div>
                                </div>

                                <!-- Footer Actions -->
                                <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                                    <button type="button" @click="closeModal()"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Cerrar
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm">
                                        Guardar
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        .resizable-column-wrapper {
            display: flex;
            align-items: center;
        }

        .resizable-column-content {
            flex: 1;
            min-width: 0;
        }

        .resizable-column-content>svg {
            flex-shrink: 0;
        }

        .resizable-column-label {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .resizable-column-handle {
            width: 10px;
            margin-right: -8px;
            cursor: col-resize;
            flex-shrink: 0;
            align-self: stretch;
            opacity: 0;
            transition: opacity 120ms ease, background-color 120ms ease;
        }

        th:hover .resizable-column-handle {
            opacity: 1;
        }

        .resizable-column-handle:hover {
            background: rgba(59, 130, 246, 0.25);
        }

        .hpr-solicitudes-table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hpr-solicitudes-table td.has-dropdown {
            overflow: visible;
        }

        .hpr-solicitudes-table th {
            white-space: nowrap;
            overflow: visible;
        }

        .hpr-solicitudes-table td>* {
            min-width: 0;
        }

        .hpr-solicitudes-table input,
        .hpr-solicitudes-table button,
        .hpr-solicitudes-table select {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .markdown-preview {
            line-height: 1.65;
        }

        .markdown-preview>*:first-child {
            margin-top: 0;
        }

        .markdown-preview h1 {
            font-size: 1.5rem;
            line-height: 2rem;
            font-weight: 800;
            margin: 1.25rem 0 0.75rem;
        }

        .markdown-preview h2 {
            font-size: 1.25rem;
            line-height: 1.75rem;
            font-weight: 800;
            margin: 1.25rem 0 0.75rem;
        }

        .markdown-preview h3 {
            font-size: 1.125rem;
            line-height: 1.75rem;
            font-weight: 700;
            margin: 1.1rem 0 0.6rem;
        }

        .markdown-preview h4,
        .markdown-preview h5,
        .markdown-preview h6 {
            font-size: 1rem;
            line-height: 1.5rem;
            font-weight: 700;
            margin: 1rem 0 0.5rem;
        }

        .markdown-preview p {
            margin: 0.6rem 0;
        }

        .markdown-preview a {
            color: rgb(37 99 235);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .markdown-preview ul,
        .markdown-preview ol {
            margin: 0.75rem 0 0.75rem 1.25rem;
            list-style-position: outside;
        }

        .markdown-preview ul {
            list-style-type: disc;
        }

        .markdown-preview ol {
            list-style-type: decimal;
        }

        .markdown-preview li {
            margin: 0.25rem 0;
        }

        .markdown-preview code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.875em;
        }

        .markdown-preview :not(pre)>code {
            background: rgb(243 244 246);
            padding: 0.15rem 0.35rem;
            border-radius: 0.375rem;
            border: 1px solid rgb(229 231 235);
        }

        .markdown-preview pre {
            position: relative;
            background: rgb(15 23 42);
            color: rgb(226 232 240);
            padding: 0.9rem 1rem;
            border-radius: 0.75rem;
            overflow: auto;
            margin: 0.9rem 0;
            border: 1px solid rgb(30 41 59);
        }

        .markdown-preview pre code {
            background: transparent;
            padding: 0;
            border: 0;
            display: block;
            white-space: pre;
        }

        .markdown-preview .md-code-wrapper {
            position: relative;
        }

        .markdown-preview .md-copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            padding: 0;
            border-radius: 0.5rem;
            border: 1px solid rgb(51 65 85);
            background: rgba(15, 23, 42, 0.8);
            color: rgb(226 232 240);
            opacity: 0;
            transition: opacity 120ms ease, background 120ms ease;
            cursor: pointer;
        }

        .markdown-preview .md-copy-btn:hover {
            background: rgba(51, 65, 85, 0.9);
        }

        .markdown-preview .md-copy-btn svg {
            width: 1rem;
            height: 1rem;
        }

        .markdown-preview .md-code-wrapper:hover .md-copy-btn {
            opacity: 1;
        }

        .markdown-preview blockquote {
            border-left: 4px solid rgb(209 213 219);
            padding-left: 1rem;
            color: rgb(75 85 99);
            margin: 0.9rem 0;
        }

        .markdown-preview table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin: 1rem 0;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            overflow: hidden;
            display: block;
            overflow-x: auto;
        }

        .markdown-preview thead {
            background: rgb(249 250 251);
        }

        .markdown-preview th,
        .markdown-preview td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid rgb(229 231 235);
            border-right: 1px solid rgb(229 231 235);
            vertical-align: top;
            white-space: nowrap;
        }

        .markdown-preview th:last-child,
        .markdown-preview td:last-child {
            border-right: 0;
        }

        .markdown-preview tbody tr:last-child td {
            border-bottom: 0;
        }

        .markdown-preview tbody tr:nth-child(even) td {
            background: rgb(249 250 251);
        }

        .markdown-preview hr {
            border: 0;
            border-top: 1px solid rgb(229 231 235);
            margin: 1.25rem 0;
        }
    </style>

    <!-- Highlight.js for syntax highlighting -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github-dark.min.css">
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>
    <!-- Common languages -->
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/javascript.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/typescript.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/php.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/sql.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/bash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/css.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/json.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/xml.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/python.min.js"></script>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        // Define functions globally so they are available immediately for Alpine
        window.updateField = function(id, field, value) {
            fetch(`/funciones/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        _method: 'PUT',
                        [field]: value
                    })
                })
                .then(async (response) => {
                    const contentType = response.headers.get('content-type') || '';
                    if (!response.ok) {
                        const text = await response.text();
                        throw new Error(text || `HTTP ${response.status}`);
                    }
                    if (contentType.includes('application/json')) return response.json();
                    return {
                        success: true
                    };
                })
                .then((data) => {
                    if (data?.success) {
                        console.log('✅ Guardado:', field, '=', value);
                        window.dispatchEvent(new CustomEvent('solicitud:updated', {
                            detail: {
                                id,
                                field,
                                value
                            }
                        }));
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        window.solicitudesApp = function() {
            return {
                boardView: 'table',
                isModalOpen: false,
                isEditing: false,
                viewMode: 'edit',
                renderedHtml: '',
                tableColStorageKey: 'hpr.solicitudes.colwidths.v2',
                form: {
                    id: null,
                    titulo: '',
                    estado: 'Nueva',
                    prioridad: 'Media',
                    asignado_a: '',
                    descripcion: '',
                    comentario: ''
                },
                initPage() {
                    this.initResizableColumns();
                    this.setupKanbanListeners();
                    this.syncKanbanCounts();
                },
                statusMeta(val) {
                    const raw = (val || '').toString().toLowerCase();
                    const normalized = raw.normalize ? raw.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : raw;
                    if (normalized === 'nueva') return {
                        dot: 'bg-gray-400',
                        bg: 'bg-gray-50',
                        text: 'text-gray-700'
                    };
                    if (normalized === 'lanzada') return {
                        dot: 'bg-blue-500',
                        bg: 'bg-blue-50',
                        text: 'text-blue-700'
                    };
                    if (normalized === 'en progreso') return {
                        dot: 'bg-purple-500',
                        bg: 'bg-purple-50',
                        text: 'text-purple-700'
                    };
                    if (normalized === 'en revision') return {
                        dot: 'bg-amber-500',
                        bg: 'bg-amber-50',
                        text: 'text-amber-700'
                    };
                    if (normalized === 'merged') return {
                        dot: 'bg-emerald-500',
                        bg: 'bg-emerald-50',
                        text: 'text-emerald-700'
                    };
                    if (normalized === 'completada') return {
                        dot: 'bg-green-600',
                        bg: 'bg-green-50',
                        text: 'text-green-700'
                    };
                    return {
                        dot: 'bg-gray-300',
                        bg: 'bg-gray-50',
                        text: 'text-gray-700'
                    };
                },
                syncKanbanCounts() {
                    const counters = this.$root?.querySelectorAll('[data-kanban-count-for]') || [];
                    for (const el of counters) {
                        const estado = el.getAttribute('data-kanban-count-for') || '';
                        const col = this.$root?.querySelector(`[data-kanban-estado="${CSS.escape(estado)}"]`);
                        const count = col ? col.querySelectorAll('[data-kanban-card]').length : 0;
                        el.textContent = String(count);
                    }
                },
                setupKanbanListeners() {
                    if (this._kanbanListenerAttached) return;
                    this._kanbanListenerAttached = true;

                    window.addEventListener('solicitud:updated', (e) => {
                        const detail = e?.detail || {};
                        if (!detail || detail.field !== 'estado') return;

                        const id = String(detail.id ?? '');
                        const estado = String(detail.value ?? '');
                        if (!id || !estado) return;

                        const card = this.$root?.querySelector(
                            `[data-kanban-card][data-solicitud-id="${CSS.escape(id)}"]`
                        );
                        const target = this.$root?.querySelector(
                            `[data-kanban-estado="${CSS.escape(estado)}"]`);
                        if (!card || !target) return;

                        card.dataset.estado = estado;
                        const addButton = target.querySelector('button[type="button"]');
                        target.insertBefore(card, addButton || null);
                        this.syncKanbanCounts();
                    });
                },
                initResizableColumns() {
                    const table = this.$root?.querySelector('table');
                    if (!table) return;

                    const theadRow = table.querySelector('thead > tr');
                    const tbodyRows = Array.from(table.querySelectorAll('tbody > tr'));
                    const colgroup = table.querySelector('colgroup');
                    if (!theadRow || !colgroup) return;

                    const applyWidthByIndex = (colIndex, widthPx, minWidthPx) => {
                        const width = Math.max(minWidthPx, Math.round(Number(widthPx) || 0));
                        const col = colgroup.children[colIndex];
                        if (col) col.style.width = `${width}px`;

                        const rows = [theadRow, ...tbodyRows];
                        for (const row of rows) {
                            const cell = row.children[colIndex];
                            if (!cell) continue;
                            cell.style.width = `${width}px`;
                            cell.style.maxWidth = `${width}px`;
                        }
                    };

                    const loadWidths = () => {
                        try {
                            const raw = localStorage.getItem(this.tableColStorageKey);
                            const parsed = raw ? JSON.parse(raw) : {};
                            return parsed && typeof parsed === 'object' ? parsed : {};
                        } catch {
                            return {};
                        }
                    };

                    const saveWidths = (widths) => {
                        try {
                            localStorage.setItem(this.tableColStorageKey, JSON.stringify(widths || {}));
                        } catch {
                            // ignore (private mode / disabled storage)
                        }
                    };

                    const minWidths = {
                        id: 20,
                        titulo: 20,
                        comentario: 20,
                        estado: 20,
                        prioridad: 20,
                        asignado_a: 20,
                        fecha: 20,
                    };

                    const stored = loadWidths();
                    const headerCells = Array.from(theadRow.children);
                    headerCells.forEach((th, i) => {
                        const key = th?.dataset?.columnKey;
                        const minW = minWidths[key] ?? 100;
                        const storedW = stored[key];
                        if (storedW) applyWidthByIndex(i, storedW, minW);
                    });

                    if (window.makeColumnsResizable) {
                        window.makeColumnsResizable(theadRow, {
                            elementsToPatch: tbodyRows,
                            columnsMinWidth: minWidths,
                            DEFAULT_MIN_COLUMN_WIDTH: 20,
                            onResizeEnd: ({
                                columnKey,
                                width
                            }) => {
                                if (!columnKey || !Number.isFinite(width)) return;
                                stored[columnKey] = Math.round(width);
                                saveWidths(stored);
                            },
                            patchWidthByIndex: (index, width, minWidth) => applyWidthByIndex(index, width,
                                minWidth),
                        });
                    }
                },
                openCreateModal() {
                    this.resetForm();
                    this.isEditing = false;
                    this.isModalOpen = true;
                    this.viewMode = 'edit';
                },
                openViewModal(solicitud) {
                    this.form = {
                        id: solicitud.id,
                        titulo: solicitud.titulo,
                        estado: solicitud.estado,
                        prioridad: solicitud.prioridad,
                        asignado_a: solicitud.asignado_a || '',
                        descripcion: solicitud.descripcion || '',
                        comentario: solicitud.comentario || ''
                    };
                    this.isEditing = true;
                    this.isModalOpen = true;
                    if (this.form.descripcion) {
                        this.viewMode = 'preview';
                        this.renderMarkdown();
                    } else {
                        this.viewMode = 'edit';
                    }
                },
                closeModal() {
                    this.isModalOpen = false;
                },
                resetForm() {
                    this.form = {
                        id: null,
                        titulo: '',
                        estado: 'Nueva',
                        prioridad: 'Media',
                        asignado_a: '',
                        descripcion: '',
                        comentario: ''
                    };
                },
                renderMarkdown() {
                    this.viewMode = 'preview';
                    this.renderedHtml = marked.parse(this.form.descripcion || '');
                    this.$nextTick(() => {
                        if (window.hprEnhanceMarkdown && this.$refs.mdPreview) {
                            window.hprEnhanceMarkdown(this.$refs.mdPreview);
                        }
                    });
                },
                updateField(id, field, value) {
                    window.updateField(id, field, value);
                },
            }
        }

        // Support for Livewire navigation (if needed for other initializations)
        document.addEventListener('livewire:navigated', () => {
            console.log('Livewire Navigated - Solicitudes Ready');
        });

        window.hprEnhanceMarkdown = function(container) {
            if (!container) return;

            const links = container.querySelectorAll('a[href]');
            for (const link of links) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }

            const blocks = container.querySelectorAll('pre');
            for (const pre of blocks) {
                const code = pre.querySelector('code');

                // Apply syntax highlighting if hljs is available and not already highlighted
                if (code && window.hljs && !code.dataset.highlighted) {
                    // Detect language from class (e.g., language-js, language-sql)
                    const langClass = Array.from(code.classList).find(c => c.startsWith('language-'));
                    if (langClass) {
                        const lang = langClass.replace('language-', '');
                        // Map common aliases
                        const langMap = {
                            'js': 'javascript',
                            'ts': 'typescript',
                            'py': 'python',
                            'sh': 'bash',
                            'shell': 'bash',
                            'html': 'xml'
                        };
                        const actualLang = langMap[lang] || lang;

                        try {
                            if (hljs.getLanguage(actualLang)) {
                                const result = hljs.highlight(code.textContent, {
                                    language: actualLang
                                });
                                code.innerHTML = result.value;
                                code.dataset.highlighted = 'yes';
                            } else {
                                // Fallback to auto-detection
                                hljs.highlightElement(code);
                            }
                        } catch (e) {
                            // If specific language fails, try auto-detection
                            hljs.highlightElement(code);
                        }
                    } else {
                        // No language specified, try auto-detection
                        hljs.highlightElement(code);
                    }
                }

                // Wrap pre in a container for proper button positioning
                if (!pre.parentElement?.classList.contains('md-code-wrapper')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'md-code-wrapper';
                    pre.parentNode.insertBefore(wrapper, pre);
                    wrapper.appendChild(pre);
                }

                // Add copy button if not already present
                const wrapper = pre.parentElement;
                if (wrapper.querySelector('.md-copy-btn')) continue;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'md-copy-btn';
                button.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2h-4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8"/><path d="M16.706 2.706A2.4 2.4 0 0 0 15 2v5a1 1 0 0 0 1 1h5a2.4 2.4 0 0 0-.706-1.706z"/><path d="M5 7a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 1.732-1"/></svg>';
                button.title = 'Copiar código';

                const copyIcon =
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2h-4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8"/><path d="M16.706 2.706A2.4 2.4 0 0 0 15 2v5a1 1 0 0 0 1 1h5a2.4 2.4 0 0 0-.706-1.706z"/><path d="M5 7a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 1.732-1"/></svg>';
                const checkIcon =
                    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';

                button.addEventListener('click', async () => {
                    const codeEl = pre.querySelector('code');
                    const text = (codeEl ? codeEl.innerText : pre.innerText) || '';

                    try {
                        await navigator.clipboard.writeText(text);
                        button.innerHTML = checkIcon;
                        button.style.color = 'rgb(74 222 128)';
                        setTimeout(() => {
                            button.innerHTML = copyIcon;
                            button.style.color = '';
                        }, 1200);
                    } catch (e) {
                        const range = document.createRange();
                        range.selectNodeContents(codeEl || pre);
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        try {
                            document.execCommand('copy');
                            button.innerHTML = checkIcon;
                            button.style.color = 'rgb(74 222 128)';
                            setTimeout(() => {
                                button.innerHTML = copyIcon;
                                button.style.color = '';
                            }, 1200);
                        } finally {
                            selection.removeAllRanges();
                        }
                    }
                });

                wrapper.appendChild(button);
            }
        };

        window.preventDefault = function(e) {
            e.preventDefault();
        };

        // Based on the logic from: https://dev.to/gohomewho/table-make-columns-resizable-2l3h
        window.makeColumnsResizable = function(columnsContainer, options = {}) {
            const {
                elementsToPatch = [],
                    columnsMinWidth = {},
                    DEFAULT_MIN_COLUMN_WIDTH = 100,
                    onResizeEnd = null,
                    patchWidthByIndex = null,
            } = options;

            columnsContainer.classList.add('resizable-columns-container');
            const _elementsToPatch = [columnsContainer, ...elementsToPatch];

            const columnElements = [...columnsContainer.children];
            columnElements.forEach((column) => {
                column.classList.add('resizable-column');
            });

            columnsContainer.addEventListener(
                'pointerdown',
                (e) => {
                    const resizeHandle = e.target.closest('.resizable-column-handle');
                    if (!resizeHandle) return;

                    e.stopPropagation();

                    const column = e.target.closest('.resizable-column');
                    const indexOfColumn = [...columnsContainer.children].indexOf(column);
                    const columnKey = column?.dataset?.columnKey;
                    const minColumnWidth = Number(columnsMinWidth[columnKey] ?? DEFAULT_MIN_COLUMN_WIDTH);

                    document.addEventListener('selectstart', window.preventDefault);

                    const initialColumnWidth = parseFloat(getComputedStyle(column).width);
                    const initialCursorX = e.clientX;

                    const elementsToResize = _elementsToPatch
                        .map((container) => container.children[indexOfColumn])
                        .filter(Boolean);

                    let lastWidth = initialColumnWidth;

                    function applyWidth(width) {
                        const nextWidth = Math.max(width, minColumnWidth);
                        lastWidth = nextWidth;

                        requestAnimationFrame(() => {
                            if (typeof patchWidthByIndex === 'function') {
                                patchWidthByIndex(indexOfColumn, nextWidth, minColumnWidth);
                            }

                            elementsToResize.forEach((element) => {
                                element.style.width = `${nextWidth}px`;
                                element.style.maxWidth = `${nextWidth}px`;
                            });
                        });
                    }

                    function handleMove(e) {
                        const newCursorX = e.clientX;
                        const moveDistance = newCursorX - initialCursorX;
                        applyWidth(initialColumnWidth + moveDistance);
                    }

                    document.addEventListener('pointermove', handleMove);

                    document.addEventListener(
                        'pointerup',
                        () => {
                            document.removeEventListener('pointermove', handleMove);
                            document.removeEventListener('selectstart', window.preventDefault);

                            if (typeof onResizeEnd === 'function') {
                                onResizeEnd({
                                    columnKey,
                                    width: lastWidth,
                                    index: indexOfColumn
                                });
                            }
                        }, {
                            once: true
                        }
                    );
                }, {
                    capture: true
                }
            );
        };
    </script>
</x-app-layout>
