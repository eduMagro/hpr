<x-app-layout>
    <x-slot name="title">Solicitudes de Compra</x-slot>

    <div class="px-4 py-8 max-w-7xl mx-auto" x-data="solicitudesCompraApp()">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span
                        class="p-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </span>
                    Solicitudes de Compra
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1 ml-14">Gestiona tus peticiones de material para Big Mat
                </p>
            </div>
            <button @click="openCreateModal()"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-2xl shadow-lg shadow-indigo-200 dark:shadow-none transition-all hover:scale-105 active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Solicitud
            </button>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div
                class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 rounded-2xl flex items-center gap-3 animate-fade-in-down">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 text-red-700 dark:text-red-300 rounded-2xl flex items-center gap-3 animate-fade-in-down">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Tabs -->
        <div class="flex items-center gap-2 mb-6 border-b border-gray-200 dark:border-gray-700 pb-1 overflow-x-auto">
            <button @click="activeTab = 'mis_solicitudes'"
                :class="activeTab === 'mis_solicitudes' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'text-gray-500 border-transparent hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 font-medium text-sm border-b-2 transition-colors whitespace-nowrap">
                Mis Solicitudes
            </button>
            @if ($pendientesAprobar->isNotEmpty() || auth()->user()->esOficina() || auth()->user()->rol === 'admin')
                <button @click="activeTab = 'por_aprobar'"
                    :class="activeTab === 'por_aprobar' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'text-gray-500 border-transparent hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-4 py-2 font-medium text-sm border-b-2 transition-colors whitespace-nowrap flex items-center gap-2">
                    Por Aprobar
                    @if($pendientesAprobar->count() > 0)
                        <span
                            class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded-full font-bold">{{ $pendientesAprobar->count() }}</span>
                    @endif
                </button>
            @endif
        </div>

        <!-- Content -->

        <!-- Tab: Mis Solicitudes -->
        <div x-show="activeTab === 'mis_solicitudes'" class="space-y-4">
            @forelse ($misSolicitudes as $solicitud)
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row gap-4 items-start md:items-center hover:shadow-md transition-shadow">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs font-mono text-gray-400">#{{ $solicitud->id }}</span>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full" class="
                                        @if($solicitud->estado === 'pendiente') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                        @elseif($solicitud->estado === 'aprobada') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                                        @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                        @endif
                                    ">
                                {{ ucfirst($solicitud->estado) }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $solicitud->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        <!-- Listado Parseado -->
                        <div class="text-gray-800 dark:text-gray-200 text-sm leading-relaxed">
                            @if(Str::startsWith($solicitud->descripcion, '#'))
                                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 mt-2">
                                    @foreach(explode('#', substr($solicitud->descripcion, 1)) as $item)
                                        @if(!empty($item))
                                            @php 
                                                $parts = explode('%', $item); 
                                                $nombre = $parts[0] ?? $item;
                                                $cantidad = $parts[1] ?? '1';
                                            @endphp
                                            <li class="flex items-center gap-2 text-sm">
                                                <span class="flex-shrink-0 w-6 h-6 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                                    {{ $cantidad }}
                                                </span>
                                                <span class="text-gray-700 dark:text-gray-300 font-medium truncate" title="{{$nombre}}">{{ $nombre }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                <p class="whitespace-pre-wrap">{{ $solicitud->descripcion }}</p>
                            @endif
                        </div>

                        @if($solicitud->fecha_aprobacion)
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Aprobado por {{ $solicitud->encargado->nombre_completo ?? 'Sistema' }} el
                                {{ $solicitud->fecha_aprobacion->format('d/m/Y H:i') }}
                            </p>
                        @endif
                        @if($solicitud->estado === 'rechazada' && $solicitud->encargado)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-2">
                                Rechazada por {{ $solicitud->encargado->nombre_completo }}
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 self-end md:self-center">
                        @if($solicitud->estado === 'aprobada')
                            <button @click="showQr({{ $solicitud->id }})"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-black dark:bg-white dark:hover:bg-gray-100 dark:text-gray-900 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                Ver QR
                            </button>
                        @endif
                        <span class="text-gray-300 dark:text-gray-600 text-4xl font-thin hidden md:inline ml-2">|</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">No tienes solicitudes</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Crea una nueva solicitud de compra para comenzar.</p>
                </div>
            @endforelse
        </div>

        <!-- Tab: Por Aprobar (Solo si hay permisos) -->
        <div x-show="activeTab === 'por_aprobar'" class="space-y-4" x-cloak>
            @forelse ($pendientesAprobar as $solicitud)
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 shadow-sm border border-l-4 border-l-orange-400 border-gray-100 dark:border-gray-700 flex flex-col gap-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">
                                {{ strtoupper(substr($solicitud->creador->name ?? '?', 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $solicitud->creador->nombre_completo ?? 'Desconocido' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Solicitado el
                                    {{ $solicitud->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-mono text-gray-400">#{{ $solicitud->id }}</span>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700">
                        @if(Str::startsWith($solicitud->descripcion, '#'))
                            <ul class="space-y-1">
                                @foreach(explode('#', substr($solicitud->descripcion, 1)) as $item)
                                    @if(!empty($item))
                                        @php 
                                            $parts = explode('%', $item); 
                                            $nombre = $parts[0] ?? $item;
                                            $cantidad = $parts[1] ?? '1';
                                        @endphp
                                        <li class="flex items-center gap-2 text-sm">
                                            <span class="flex-shrink-0 w-5 h-5 rounded bg-white dark:bg-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-600 border border-gray-200 dark:border-gray-600">
                                                {{ $cantidad }}
                                            </span>
                                            <span class="text-gray-800 dark:text-gray-200">{{ $nombre }}</span>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-800 dark:text-gray-200 text-sm whitespace-pre-wrap">{{ $solicitud->descripcion }}</p>
                        @endif
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <form action="{{ route('solicitudes-compra.rechazar', $solicitud->id) }}" method="POST"
                            class="inline">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 rounded-xl text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-sm font-medium transition-colors">
                                Rechazar
                            </button>
                        </form>
                        <form action="{{ route('solicitudes-compra.aprobar', $solicitud->id) }}" method="POST"
                            class="inline">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Aprobar y Generar QR
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <p class="text-gray-500">No hay solicitudes pendientes de aprobar.</p>
                </div>
            @endforelse
        </div>

        <!-- Create Modal -->
        <div x-show="isCreateModalOpen" 
             style="display: none;"
             class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen md:px-4 md:pt-4 md:pb-20 text-center sm:p-0">
                <div x-show="isCreateModalOpen" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm" @click="closeCreateModal()"></div>

                <div x-show="isCreateModalOpen"
                     x-transition:enter="ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block h-screen md:h-auto w-screen md:w-full md:max-w-xl p-8 md:my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl md:rounded-[2rem]">
                    
                    <div class="flex items-center justify-between mb-8 pb-8 border-b border-gray-200 dark:border-gray-600">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Nueva Solicitud</h3>
                        <button @click="closeCreateModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <!-- Formulario con Items Dinámicos -->
                    <form action="{{ route('solicitudes-compra.store') }}" method="POST" x-data="{
                        items: [{ nombre: '', cantidad: 1 }],
                        addItem() {
                            this.items.push({ nombre: '', cantidad: 1 });
                            // Auto-focus next input tick
                            this.$nextTick(() => {
                                const inputs = document.querySelectorAll('.item-name-input');
                                if (inputs.length > 0) inputs[inputs.length - 1].focus();
                            });
                        },
                        removeItem(index) {
                            if (this.items.length > 1) {
                                this.items.splice(index, 1);
                            }
                        },
                        submitForm(e) {
                            // Construir string con formato: #Nombre%Cantidad
                            // Filtramos items vacíos
                            const validItems = this.items.filter(i => i.nombre.trim() !== '');
                            if (validItems.length === 0) {
                                alert('Añade al menos un artículo.');
                                e.preventDefault();
                                return;
                            }
                            
                            // Formato: #Nombre%Cantidad#Nombre2%Cantidad2
                            const descripcionString = validItems.map(i => `#${i.nombre.trim()}%${i.cantidad}`).join('');
                            document.getElementById('descripcionInput').value = descripcionString;
                        }
                    }" @submit="submitForm">
                        @csrf
                        <input type="hidden" name="descripcion" id="descripcionInput">

                        <div class="space-y-3 mb-8 mt-8 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar p-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2 ml-1">Lista de Artículos</label>
                            
                            <template x-for="(item, index) in items" :key="index">
                                <div class="flex max-md:flex-col gap-3 items-center group animate-fade-in-up">
                                    <!-- Nombre -->
                                    <div class="flex-grow w-full">
                                        <input type="text" x-model="item.nombre" required
                                            @keydown.enter.prevent="addItem()"
                                            class="item-name-input block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-gray-900 dark:text-white font-medium py-3 px-4 shadow-sm h-[50px]"
                                            placeholder="Nombre del artículo">
                                    </div>

                                    <div class="flex gap-2 w-full justify-between">
                                    <!-- Cantidad con botones -->
                                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-full p-1 shadow-inner h-[50px] flex-shrink-0">
                                        <button type="button" @click="if(item.cantidad > 1) item.cantidad--"
                                            class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                        
                                        <div class="w-12 text-center flex items-center justify-center">
                                            <input type="number" x-model="item.cantidad" readonly
                                                class="w-full text-center bg-transparent border-none p-0 text-gray-900 dark:text-white font-bold text-lg focus:ring-0 cursor-default select-none pointer-events-none appearance-none text-center" 
                                                style="-moz-appearance: textfield;">
                                        </div>
                                            
                                        <button type="button" @click="item.cantidad++"
                                            class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>
                                    </div>

                                    <!-- Eliminar -->
                                    <button type="button" @click="removeItem(index)" 
                                        class="p-3 text-gray-300 hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400 transition-colors rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 flex-shrink-0"
                                        :class="{ 'opacity-0 pointer-events-none': items.length === 1 }">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-6">
                            <button type="button" @click="addItem()" 
                                class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors py-2 px-3 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                Añadir otro artículo
                            </button>

                            <div class="flex gap-3">
                                <button type="submit" 
                                    class="px-8 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-2xl shadow-lg shadow-indigo-200 dark:shadow-none transition-all hover:scale-105 active:scale-95 flex items-center gap-2">
                                    <span>Crear Solicitud</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- QR Modal -->
        <div x-show="isQrModalOpen" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog"
            aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="isQrModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm"
                    @click="isQrModalOpen = false"></div>

                <div x-show="isQrModalOpen"
                    class="inline-block w-full max-w-sm p-8 my-8 overflow-hidden text-center align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-3xl border border-gray-100 dark:border-gray-700 relative">

                    <button @click="isQrModalOpen = false"
                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" x-text="qrTitle">QR de Compra</h3>
                    <p class="text-sm text-gray-500 mb-6">Muestra este código en Big Mat</p>

                    <div class="flex justify-center mb-6 bg-white p-4 rounded-xl inline-block" x-html="qrSvg">
                        <!-- QR Place holder -->
                        <div class="animate-pulse w-48 h-48 bg-gray-200 rounded-lg"></div>
                    </div>

                    <a :href="qrUrl" target="_blank" class="text-xs text-indigo-500 hover:underline break-all"
                        x-text="qrUrl"></a>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('solicitudesCompraApp', () => ({
                activeTab: 'mis_solicitudes',
                isCreateModalOpen: false,
                isQrModalOpen: false,
                qrSvg: '',
                qrUrl: '',
                qrTitle: '',

                openCreateModal() {
                    this.isCreateModalOpen = true;
                },

                closeCreateModal() {
                    this.isCreateModalOpen = false;
                },

                async showQr(id) {
                    this.qrSvg = '<div class="w-48 h-48 flex items-center justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div></div>';
                    this.isQrModalOpen = true;
                    this.qrTitle = 'Cargando...';
                    this.qrUrl = '';

                    try {
                        const response = await fetch(`/solicitudes-compra/${id}/ver-qr`);
                        const data = await response.json();

                        if (data.error) {
                            alert(data.error);
                            this.isQrModalOpen = false;
                            return;
                        }

                        this.qrSvg = data.qr_svg;
                        this.qrUrl = data.url;
                        this.qrTitle = data.titulo;
                    } catch (error) {
                        console.error('Error fetching QR:', error);
                        this.qrSvg = '<p class="text-red-500">Error cargando QR</p>';
                    }
                }
            }));
        });
    </script>
</x-app-layout>