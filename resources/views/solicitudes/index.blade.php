<x-app-layout>
    <x-slot name="title">Funciones - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Panel de Funciones"
        subtitle="Accesos rápidos y utilidades del sistema"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>'
    />

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(248, 250, 252, 0.05);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 10px;
            border: 2px solid transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Para Firefox */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #475569 transparent;
        }
    </style>

    <div class="px-4 py-6 max-w-[1920px] mx-auto"
        x-data="solicitudesApp(@js($solicitudes), @js($users), @js($estados), @js($prioridades))" x-init="initPage()">

        <!-- Header con gradiente -->
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 p-6 rounded-2xl bg-gradient-to-tr lg:h-[75px] from-gray-800 to-gray-900 shadow-lg dark:from-gray-700 dark:to-gray-800">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-xl bg-gradient-to-tr from-gray-600 to-gray-700 dark:from-gray-500 dark:to-gray-600 backdrop-blur-sm flex items-center justify-center text-white shadow-inner">
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
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 font-semibold text-sm flex items-center gap-2 hover:scale-105 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Función
                </button>
            </div>
        </div>

        <!-- Notion-like Tabs -->
        <div class="flex items-center gap-6 mb-4 text-sm pb-1 overflow-x-auto ml-4 custom-scrollbar">
            <button @click="boardView = 'table'"
                :class="boardView === 'table' ? 'text-gray-900 dark:text-white border-b-2 border-gray-900 dark:border-white font-medium' :
                    'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 border-b-2 border-transparent'"
                class="flex items-center gap-2 px-2 py-2 whitespace-nowrap transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Vista Tabla
            </button>

            <button @click="boardView = 'estado'; $nextTick(() => syncKanbanCounts())"
                :class="boardView === 'estado' ? 'text-gray-900 dark:text-white border-b-2 border-gray-900 dark:border-white font-medium' :
                    'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 border-b-2 border-transparent'"
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
            class="h-[calc(100vh-35vh-20px)] overflow-y-auto select-none custom-scrollbar">
            <div class="overflow-x-auto min-h-[400px] h-full p-1">
                <table
                    class="w-full text-sm hpr-solicitudes-table bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow">
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
                        <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-700 dark:border-gray-700">
                            <!-- ID Column -->
                            <th data-column-key="id"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-16 border-r border-gray-200 dark:border-gray-500 group">
                                <div class="resizable-column-wrapper">
                                    <div class="resizable-column-content flex items-center justify-center gap-1">
                                        <span class="resizable-column-label uppercase text-[10px]">#</span>
                                    </div>
                                    <div class="resizable-column-handle" aria-hidden="true"></div>
                                </div>
                            </th>

                            <!-- Title Column -->
                            <th data-column-key="titulo"
                                class="relative px-2 py-2 text-left font-medium text-gray-500 min-w-[20px] border-r border-gray-200 dark:border-gray-500 group">
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
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-64 border-r border-gray-200 dark:border-gray-500 group">
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
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-40 border-r border-gray-200 dark:border-gray-500 group">
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
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-32 border-r border-gray-200 dark:border-gray-500 group">
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
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-48 border-r border-gray-200 dark:border-gray-500 group">
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
                                class="relative px-2 py-2 text-left font-medium text-gray-500 w-48 group">
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
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="solicitud in solicitudes" :key="solicitud.id">
                            <tr class="group hover:bg-gray-700 transition-all duration-200 relative">
                                <!-- ID -->
                                <td class="px-2 py-1 border-r border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 text-xs font-mono w-16 text-center"
                                    x-text="solicitud.id">
                                </td>

                                <!-- Titulo & Open Button -->
                                <td class="px-2 py-1 border-r border-gray-200 dark:border-gray-700 relative group/cell">
                                    <div class="relative flex items-center h-8">
                                        <input type="text" :value="solicitud.titulo"
                                            @change="updateField(solicitud.id, 'titulo', $event.target.value); solicitud.titulo = $event.target.value"
                                            class="w-full h-full bg-transparent border-none focus:ring-1 focus:ring-blue-500 rounded text-sm text-gray-900 dark:text-white font-medium placeholder-gray-400 px-1"
                                            placeholder="Sin título">

                                        <button @click="openViewModal(solicitud)"
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
                                <td class="px-2 py-1 border-r border-gray-200 dark:border-gray-700">
                                    <input type="text" :value="solicitud.comentario"
                                        @change="updateField(solicitud.id, 'comentario', $event.target.value); solicitud.comentario = $event.target.value"
                                        class="w-full h-8 px-2 bg-transparent border-none focus:ring-1 focus:ring-blue-500 rounded text-sm text-gray-600 dark:text-gray-300 placeholder-gray-300 dark:placeholder-gray-600 truncate"
                                        placeholder="Vacío">
                                </td>

                                <!-- Estado -->
                                <td class="px-2 py-1 border-r border-gray-200 dark:border-gray-700 has-dropdown">
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click="open = !open" type="button"
                                            class="flex items-center gap-2 h-7 w-full px-2 rounded text-[11px] font-bold transition-all border border-transparent hover:border-gray-200 shadow-sm"
                                            :class="getColorEstado(solicitud.estado).bg + ' ' + getColorEstado(solicitud.estado)
                                                .text">
                                            <span class="w-2 h-2 rounded-full shrink-0"
                                                :class="getColorEstado(solicitud.estado).dot"></span>
                                            <span class="flex-1 text-left" x-text="solicitud.estado"></span>
                                            <svg class="w-3 h-3 opacity-40 shrink-0" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            class="absolute z-10 mt-1 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 overflow-hidden left-0"
                                            x-cloak style="display: none;">
                                            <template x-for="est in estados">
                                                <button
                                                    @click="solicitud.estado = est; open = false; updateField(solicitud.id, 'estado', est)"
                                                    type="button"
                                                    class="flex items-center gap-2 w-full px-3 py-1.5 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                                    :class="getColorEstado(est).text">
                                                    <span class="w-2 h-2 rounded-full shrink-0"
                                                        :class="getColorEstado(est).dot"></span>
                                                    <span x-text="est"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </td>

                                <!-- Prioridad -->
                                <td class="px-2 py-1 border-r border-gray-200 dark:border-gray-700 has-dropdown">
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click="open = !open" type="button"
                                            class="flex items-center gap-2 h-7 w-full px-2 rounded text-[11px] font-bold transition-all border border-transparent hover:border-gray-200 shadow-sm"
                                            :class="getColorPrioridad(solicitud.prioridad).bg + ' ' + getColorPrioridad(
                                                solicitud.prioridad).text">
                                            <span class="w-2 h-2 rounded-full shrink-0"
                                                :class="getColorPrioridad(solicitud.prioridad).dot"></span>
                                            <span class="flex-1 text-left" x-text="solicitud.prioridad"></span>
                                            <svg class="w-3 h-3 opacity-40 shrink-0" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            class="absolute z-10 mt-1 w-32 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 overflow-hidden left-0"
                                            x-cloak style="display: none;">
                                            <template x-for="pri in prioridades">
                                                <button
                                                    @click="solicitud.prioridad = pri; open = false; updateField(solicitud.id, 'prioridad', pri)"
                                                    type="button"
                                                    class="flex items-center gap-2 w-full px-3 py-1.5 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                                    :class="getColorPrioridad(pri).text">
                                                    <span class="w-2 h-2 rounded-full shrink-0"
                                                        :class="getColorPrioridad(pri).dot"></span>
                                                    <span x-text="pri"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </td>

                                <!-- Asignado -->
                                <td class="px-2 py-1 border-r border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-2 h-full">
                                        <div x-show="solicitud.asignado_a" x-cloak
                                            class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-[9px] font-bold text-indigo-600 shrink-0 shadow-sm border border-indigo-200"
                                            x-text="() => {
                                                if (!solicitud.asignado_a) return '';
                                                const u = users.find(u => u.id == solicitud.asignado_a);
                                                return u ? u.name.charAt(0).toUpperCase() : '';
                                            }">
                                        </div>
                                        <select :value="solicitud.asignado_a || ''"
                                            @change="updateField(solicitud.id, 'asignado_a', $event.target.value); solicitud.asignado_a = $event.target.value"
                                            class="w-full h-7 pl-0 pr-6 py-0 bg-transparent border-none focus:ring-0 rounded text-[11px] font-bold text-gray-600 dark:text-gray-300 cursor-pointer text-ellipsis overflow-hidden">
                                            <option value="" class="dark:bg-gray-800">Sin asignar</option>
                                            <template x-for="user in users">
                                                <option :value="user.id" x-text="user.name" class="dark:bg-gray-800"
                                                    :selected="solicitud.asignado_a == user.id"></option>
                                            </template>
                                        </select>
                                    </div>
                                </td>

                                <!-- Fecha -->
                                <td class="px-2 py-1 text-xs text-gray-500" x-text="formatDate(solicitud.created_at)">
                                </td>
                            </tr>
                        </template>
                        <template x-if="solicitudes.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    No hay solicitudes registradas
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>


        </div>

        <!-- Kanban Board View -->
        <div x-show="boardView === 'estado'" class="pb-24" x-cloak>
            <div class="flex gap-4 overflow-x-auto pb-4 items-start kanban-scrollbar">
                <template x-for="est in estados" :key="est">
                    <div
                        class="w-80 shrink-0 rounded-2xl border overflow-hidden border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm flex flex-col group/col">
                        <!-- Column Header -->
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3"
                            :class="getColorEstado(est).bg">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="getColorEstado(est).dot"></span>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold truncate" :class="getColorEstado(est).text"
                                        x-text="est">
                                    </div>
                                </div>
                            </div>
                            <span
                                class="text-xs font-bold px-2 py-0.5 rounded-full bg-white/70 dark:bg-gray-800/70 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400"
                                x-text="solicitudes.filter(s => s.estado === est).length"></span>
                        </div>

                        <div class="p-3 space-y-2" :data-kanban-estado="est">
                            <template x-for="solicitud in solicitudes.filter(s => s.estado === est)"
                                :key="solicitud.id">
                                <div :data-solicitud-id="solicitud.id" :data-estado="solicitud.estado"
                                    class="group rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:shadow-md transition-shadow">
                                    <button type="button" @click="openViewModal(solicitud)"
                                        class="w-full text-left p-3">
                                        <div class="flex items-start gap-2">
                                            <div class="w-2 h-2 rounded-full mt-1.5 shrink-0"
                                                :class="getColorEstado(est).dot"></div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2"
                                                    x-text="solicitud.titulo">
                                                </div>
                                                <div x-show="solicitud.comentario"
                                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2"
                                                    x-text="solicitud.comentario">
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </template>

                            <button type="button" @click="openCreateModal(); form.estado = est"
                                class="w-full rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-600 dark:text-gray-400 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Nueva solicitud
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>


        <!-- Create/Edit Modal (Side Drawer) con animaciones -->
        <div x-show="isModalOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-[70] overflow-hidden" style="display: none;"
            aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()">
                </div>
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    <div x-show="isModalOpen" x-transition:enter="transform transition ease-out duration-300"
                        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in duration-200"
                        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                        class="pointer-events-auto w-screen max-w-2xl bg-white dark:bg-gray-800 shadow-2xl flex flex-col h-full rounded-l-2xl">

                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 dark:bg-gray-800 flex items-center justify-between bg-white z-20">
                            <div
                                class="flex items-center gap-2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                </svg>
                                <span class="text-sm">Abierto como página</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <button @click="closeModal()"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md p-1.5 transition-colors"
                                    title="Cerrar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M17 12H3" />
                                        <path d="m11 18 6-6-6-6" />
                                        <path d="M21 5v14" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto bg-white dark:bg-gray-800 custom-scrollbar">
                            <form :action="isEditing ? '/funciones/' + form.id : '{{ route('funciones.store') }}'"
                                method="POST" id="solicitudForm" class="h-full flex flex-col">
                                @csrf
                                <template x-if="isEditing">
                                    <input type="hidden" name="_method" value="PUT">
                                </template>

                                <!-- Notion-like Top Section -->
                                <div class="px-10 pt-10 pb-4">
                                    <!-- Title -->
                                    <div class="relative">
                                        <textarea x-ref="tituloTextarea" name="titulo" x-model="form.titulo"
                                            placeholder="Nombre de la función" :disabled="isCreating"
                                            @input.debounce.1000ms="autoSaveField('titulo', form.titulo)"
                                            @keydown.enter.prevent="$el.blur()"
                                            class="w-full text-4xl font-bold border-none bg-transparent focus:ring-0 placeholder-gray-300 dark:placeholder-gray-600 px-0 mb-6 text-gray-900 dark:text-white leading-tight resize-none overflow-hidden"
                                            rows="1"
                                            @input="$el.style.height = ''; $el.style.height = $el.scrollHeight + 'px'"></textarea>

                                        <!-- Creating indicator overlay -->
                                        <div x-show="isCreating"
                                            class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 backdrop-blur-[1px] flex items-center justify-start gap-3 text-gray-400 dark:text-gray-500 font-medium">
                                            <svg class="animate-spin h-6 w-6" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <span class="text-xl">Preparando registro...</span>
                                        </div>
                                    </div>

                                    <!-- Properties -->
                                    <div class="space-y-1 mb-8">
                                        <!-- Estado -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div
                                                class="w-36 text-gray-500 dark:text-gray-400 flex items-center gap-2 text-sm">
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
                                                    @change="autoSaveField('estado', form.estado)"
                                                    class="border-none bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded px-2 py-1 pr-8 cursor-pointer focus:ring-0 w-auto transition-colors">
                                                    @foreach ($estados as $estado)
                                                        <option value="{{ $estado }}" class="dark:bg-gray-800">{{ $estado }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Prioridad -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div
                                                class="w-36 text-gray-500 dark:text-gray-400 flex items-center gap-2 text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                Prioridad
                                            </div>
                                            <div class="flex-1">
                                                <select name="prioridad" x-model="form.prioridad"
                                                    @change="autoSaveField('prioridad', form.prioridad)"
                                                    class="border-none bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded px-2 py-1 pr-8 cursor-pointer focus:ring-0 w-auto transition-colors">
                                                    @foreach ($prioridades as $prioridad)
                                                        <option value="{{ $prioridad }}" class="dark:bg-gray-800">
                                                            {{ $prioridad }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Asignado -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div
                                                class="w-36 text-gray-500 dark:text-gray-400 flex items-center gap-2 text-sm">
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
                                                    @change="autoSaveField('asignado_a', form.asignado_a)"
                                                    class="border-none bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded px-2 py-1 pr-8 cursor-pointer focus:ring-0 w-auto transition-colors">
                                                    <option value="" class="dark:bg-gray-800">Sin asignar</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}" class="dark:bg-gray-800">
                                                            {{ $user->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Comentario (in Modal) -->
                                        <div class="flex items-center gap-4 py-1.5">
                                            <div
                                                class="w-36 text-gray-500 dark:text-gray-400 flex items-center gap-2 text-sm">
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
                                                    @input="scheduleAutoSave('comentario')"
                                                    class="w-full border-none bg-transparent hover:bg-gray-50 dark:hover:bg-gray-700 focus:bg-white dark:focus:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-600"
                                                    placeholder="Añadir un comentario...">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="h-px bg-gray-200 dark:bg-gray-700 w-full mb-8"></div>
                                </div>

                                <!-- Markdown Editor/Viewer -->
                                <div class="px-10 pb-10 flex-1 flex flex-col">
                                    <div
                                        class="flex items-center gap-4 mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                                        <button type="button" @click="viewMode = 'edit'"
                                            :class="{ 'text-gray-900 dark:text-white font-semibold': viewMode === 'edit', 'text-gray-500 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300': viewMode !== 'edit' }"
                                            class="pb-2 text-sm transition-colors">Editar</button>
                                        <button type="button" @click="renderMarkdown()"
                                            :class="{ 'text-gray-900 dark:text-white font-semibold': viewMode === 'preview', 'text-gray-500 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300': viewMode !== 'preview' }"
                                            class="pb-2 text-sm transition-colors">Vista Previa</button>

                                        <!-- Autosave indicator (icons only) -->
                                        <div class="flex-1 flex justify-end">
                                            <div x-show="autoSaveStatus !== 'idle'"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-50"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-50"
                                                class="flex items-center justify-center w-6 h-6">
                                                <!-- Saving spinner -->
                                                <svg x-show="autoSaveStatus === 'saving'"
                                                    class="w-4 h-4 text-gray-400 dark:text-gray-500 animate-spin"
                                                    fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="3"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                                <!-- Saved check with pop animation -->
                                                <svg x-show="autoSaveStatus === 'saved'"
                                                    x-transition:enter="transition ease-out duration-300"
                                                    x-transition:enter-start="scale-0"
                                                    x-transition:enter-end="scale-100"
                                                    class="w-4 h-4 text-emerald-500 dark:text-emerald-400" fill="none"
                                                    stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                                <!-- Error icon -->
                                                <svg x-show="autoSaveStatus === 'error'"
                                                    x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="scale-0"
                                                    x-transition:enter-end="scale-100"
                                                    class="w-4 h-4 text-red-500 dark:text-red-400" fill="none"
                                                    stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Mode -->
                                    <div x-show="viewMode === 'edit'" class="flex-1 flex flex-col min-h-0">
                                        <textarea name="descripcion" x-model="form.descripcion"
                                            @input="scheduleAutoSave()"
                                            class="w-full flex-1 min-h-[200px] border-0 focus:ring-0 text-gray-800 dark:text-gray-200 bg-transparent text-base resize-none p-0 leading-relaxed"
                                            placeholder="Escribe aquí los detalles de la función..."></textarea>
                                    </div>

                                    <!-- Preview Mode -->
                                    <div x-show="viewMode === 'preview'" x-ref="mdPreview"
                                        class="flex-1 min-h-0 overflow-y-auto markdown-preview text-gray-800 dark:text-gray-200"
                                        x-html="renderedHtml">
                                    </div>

                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        /* Estilos para la barra de desplazamiento de Kanban */
        .kanban-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .kanban-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        html.dark .kanban-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .kanban-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        html.dark .kanban-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.5);
        }

        .kanban-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.8);
            background-clip: content-box;
        }

        html.dark .kanban-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.8);
        }

        /* Firefox */
        .kanban-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) rgba(0, 0, 0, 0.05);
        }

        html.dark .kanban-scrollbar {
            scrollbar-color: rgba(75, 85, 99, 0.5) rgba(255, 255, 255, 0.05);
        }

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

        html.dark .markdown-preview :not(pre)>code {
            background: rgb(31 41 55);
            border-color: rgb(55 65 81);
            color: rgb(209 213 219);
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

        /* Custom scrollbar for code blocks - matching Firefox thin style */
        .markdown-preview pre::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        .markdown-preview pre::-webkit-scrollbar-track {
            background: transparent;
        }

        .markdown-preview pre::-webkit-scrollbar-thumb {
            background: rgb(71 85 105);
            border-radius: 3px;
        }

        .markdown-preview pre::-webkit-scrollbar-thumb:hover {
            background: rgb(100 116 139);
        }

        .markdown-preview pre::-webkit-scrollbar-corner {
            background: transparent;
        }

        /* Hide scrollbar buttons/arrows */
        .markdown-preview pre::-webkit-scrollbar-button {
            display: none;
            height: 0;
            width: 0;
        }

        /* Firefox scrollbar */
        .markdown-preview pre {
            scrollbar-width: thin;
            scrollbar-color: rgb(71 85 105) transparent;
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

        html.dark .markdown-preview blockquote {
            border-left-color: rgb(55 65 81);
            color: rgb(156 163 175);
        }

        .markdown-preview table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin: 1rem 0;
            display: block;
            overflow-x: auto;
        }

        .markdown-preview thead {
            background: rgb(249 250 251);
        }

        html.dark .markdown-preview thead {
            background: rgb(31 41 55);
        }

        .markdown-preview th,
        .markdown-preview td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid rgb(229 231 235);
            border-right: 1px solid rgb(229 231 235);
            vertical-align: top;
            white-space: nowrap;
        }

        html.dark .markdown-preview th,
        html.dark .markdown-preview td {
            border-color: rgb(55 65 81);
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

        html.dark .markdown-preview tbody tr:nth-child(even) td {
            background: rgb(31 41 55);
        }

        .markdown-preview hr {
            border: 0;
            border-top: 1px solid rgb(229 231 235);
            margin: 1.25rem 0;
        }

        html.dark .markdown-preview hr {
            border-top-color: rgb(55 65 81);
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
        window.updateField = function (id, field, value) {
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

        window.solicitudesApp = function (initialSolicitudes, allUsers, allEstados, allPrioridades) {
            return {
                solicitudes: initialSolicitudes,
                users: allUsers,
                estados: allEstados,
                prioridades: allPrioridades,
                boardView: 'table',
                isModalOpen: false,
                isEditing: false,
                isCreating: false,
                hasCreatedNew: false,
                viewMode: 'edit',
                renderedHtml: '',
                autoSaveStatus: 'idle',
                autoSaveTimeout: null,
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

                // Helper para colores de estado
                getColorEstado(val) {
                    const v = (val || '').toLowerCase();
                    if (v === 'nueva') return {
                        dot: 'bg-gray-400',
                        bg: 'bg-gray-100 dark:bg-gray-800/60',
                        text: 'text-gray-700 dark:text-gray-300'
                    };
                    if (v === 'lanzada') return {
                        dot: 'bg-blue-500',
                        bg: 'bg-blue-100 dark:bg-blue-900/40',
                        text: 'text-blue-700 dark:text-blue-300'
                    };
                    if (v === 'en progreso') return {
                        dot: 'bg-purple-500',
                        bg: 'bg-purple-100 dark:bg-purple-900/40',
                        text: 'text-purple-700 dark:text-purple-300'
                    };
                    if (v === 'en revisión' || v === 'en revision') return {
                        dot: 'bg-amber-500',
                        bg: 'bg-amber-100 dark:bg-amber-900/40',
                        text: 'text-amber-700 dark:text-amber-300'
                    };
                    if (v === 'merged') return {
                        dot: 'bg-emerald-500',
                        bg: 'bg-emerald-100 dark:bg-emerald-900/40',
                        text: 'text-emerald-700 dark:text-emerald-300'
                    };
                    if (v === 'completada') return {
                        dot: 'bg-green-600',
                        bg: 'bg-green-100 dark:bg-green-900/40',
                        text: 'text-green-700 dark:text-green-300'
                    };
                    return {
                        dot: 'bg-gray-300',
                        bg: 'bg-gray-50 dark:bg-gray-800/40',
                        text: 'text-gray-600 dark:text-gray-400'
                    };
                },

                // Helper para colores de prioridad
                getColorPrioridad(val) {
                    const v = (val || '').toLowerCase();
                    if (v === 'alta') return {
                        dot: 'bg-red-500',
                        bg: 'bg-red-100 dark:bg-red-900/40',
                        text: 'text-red-700 dark:text-red-300'
                    };
                    if (v === 'media') return {
                        dot: 'bg-yellow-500',
                        bg: 'bg-yellow-100 dark:bg-yellow-900/40',
                        text: 'text-yellow-700 dark:text-yellow-300'
                    };
                    if (v === 'baja') return {
                        dot: 'bg-sky-400',
                        bg: 'bg-sky-100 dark:bg-sky-900/40',
                        text: 'text-sky-700 dark:text-sky-300'
                    };
                    return {
                        dot: 'bg-gray-300',
                        bg: 'bg-gray-50 dark:bg-gray-800/40',
                        text: 'text-gray-600 dark:text-gray-400'
                    };
                },

                // Helper para formatear fecha
                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('es-ES', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });
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
                        if (!detail.id || !detail.field) return;

                        // 1. Sincronizar el array local de solicitudes para que el modal y otras vistas tengan datos frescos
                        const index = this.solicitudes.findIndex(s => s.id == detail.id);
                        if (index !== -1) {
                            this.solicitudes[index][detail.field] = detail.value;
                        }

                        // 2. Lógica específica para mover tarjetas en el tablero Kanban (solo si el campo es estado)
                        if (detail.field !== 'estado') return;

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
                async openCreateModal() {
                    this.resetForm();
                    this.isCreating = true;
                    this.hasCreatedNew = false;
                    this.isModalOpen = true;
                    this.viewMode = 'edit';

                    try {
                        const response = await fetch('{{ route('funciones.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                titulo: '', // Vacío por defecto
                                estado: 'Nueva',
                                prioridad: 'Baja', // Baja por defecto
                                asignado_a: null,
                                descripcion: '',
                                comentario: ''
                            })
                        });

                        if (!response.ok) throw new Error('Error creating solicitud');

                        const data = await response.json();

                        // Añadir a la lista reactiva
                        this.solicitudes.unshift(data);

                        this.form.id = data.id;
                        this.form.titulo = '';
                        this.form.prioridad = 'Baja';
                        this.isEditing = true;
                        this.isCreating = false;
                        this.hasCreatedNew = true;

                        // Focus en el textarea del título usando refs
                        this.$nextTick(() => {
                            if (this.$refs.tituloTextarea) {
                                this.$refs.tituloTextarea.focus();
                            }
                        });

                        console.log('✅ Nueva función creada con ID:', data.id);

                    } catch (error) {
                        console.error('Error creando función:', error);
                        this.isCreating = false;
                        this.isModalOpen = false;
                        alert('Error al crear la función. Por favor intenta de nuevo.');
                    }
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
                        this.$nextTick(() => {
                            if (this.$refs.tituloTextarea) {
                                this.$refs.tituloTextarea.focus();
                            }
                        });
                    }
                },
                closeModal() {
                    this.isModalOpen = false;
                    this.isCreating = false;

                    // Si se creó una nueva función, recargamos para que aparezca en la tabla
                    if (this.hasCreatedNew) {
                        window.location.reload();
                    }
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
                scheduleAutoSave(field = 'descripcion') {
                    // Only autosave if we're editing an existing record
                    if (!this.isEditing || !this.form.id) return;

                    // Clear any pending save for this field
                    if (this.autoSaveTimeout) {
                        clearTimeout(this.autoSaveTimeout);
                    }

                    // Schedule new save after 1 second of inactivity
                    this.autoSaveTimeout = setTimeout(() => {
                        this.autoSaveField(field, this.form[field]);
                    }, 1000);
                },
                async autoSaveField(field, value) {
                    if (!this.form.id) return;
                    if (!this.isEditing) return;

                    this.autoSaveStatus = 'saving';

                    try {
                        const response = await fetch(`/funciones/${this.form.id}`, {
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
                        });

                        if (!response.ok) throw new Error('Save failed');

                        this.autoSaveStatus = 'saved';
                        console.log(`✅ Autoguardado: ${field} = ${value}`);

                        // Dispatch event to update table row if needed
                        window.dispatchEvent(new CustomEvent('solicitud:updated', {
                            detail: {
                                id: this.form.id,
                                field,
                                value
                            }
                        }));

                        // Hide the indicator after 2 seconds
                        setTimeout(() => {
                            if (this.autoSaveStatus === 'saved') {
                                this.autoSaveStatus = 'idle';
                            }
                        }, 2000);
                    } catch (error) {
                        console.error('Error en autoguardado:', error);
                        this.autoSaveStatus = 'error';

                        // Hide error after 3 seconds
                        setTimeout(() => {
                            if (this.autoSaveStatus === 'error') {
                                this.autoSaveStatus = 'idle';
                            }
                        }, 3000);
                    }
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

        window.hprEnhanceMarkdown = function (container) {
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

        window.preventDefault = function (e) {
            e.preventDefault();
        };

        // Based on the logic from: https://dev.to/gohomewho/table-make-columns-resizable-2l3h
        window.makeColumnsResizable = function (columnsContainer, options = {}) {
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