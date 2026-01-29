<x-app-layout>
    <x-slot name="title">Gestión de Incidencias - {{ config('app.name') }}</x-slot>

    <x-page-header title="Panel de Incidencias" subtitle="Gestión de averías y mantenimiento correctivo"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' />

    <div class="px-4 py-6 max-w-7xl mx-auto">

        {{-- Header & Controls --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">

            <div class="flex items-center gap-3">
                <button type="button" onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl shadow-lg shadow-red-500/20 transition-all font-bold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Nueva Incidencia
                </button>
            </div>
        </div>

        {{-- Filters Bar --}}
        <div x-data="{
            currentTab: '{{ request('ver_inactivas') ? 'historial' : 'pendientes' }}',
            transitioning: false,
            async handleClick(tab) {
                if (this.currentTab === tab || this.transitioning) return;
        
                this.transitioning = true;
                const isHistorial = tab === 'historial';
                const container = document.getElementById('incidencias-list-container');
        
                // 1. Exit animation
                container.style.transform = isHistorial ? 'translateX(-20px)' : 'translateX(20px)';
                container.style.opacity = '0';
        
                // Wait for exit animation
                await new Promise(r => setTimeout(r, 100));
        
                // 2. Fetch new data
                const url = new URL('{{ route('incidencias.list.ajax') }}');
                if (isHistorial) url.searchParams.append('ver_inactivas', '1');
        
                try {
                    const response = await fetch(url);
                    const html = await response.text();
        
                    // 3. Update content
                    container.innerHTML = html;
                    this.currentTab = tab;
        
                    // Modify the URL without reloading
                    const newUrl = new URL(window.location);
                    if (isHistorial) {
                        newUrl.searchParams.set('ver_inactivas', '1');
                    } else {
                        newUrl.searchParams.delete('ver_inactivas');
                    }
                    window.history.pushState({}, '', newUrl);
        
                    // 4. Prepare for entrance
                    container.style.transition = 'none';
                    container.style.transform = isHistorial ? 'translateX(20px)' : 'translateX(-20px)';
        
                    // Force reflow
                    void container.offsetWidth;
        
                    // 5. Enter animation
                    container.style.transition = 'all 150ms ease-out';
                    container.style.transform = 'translateX(0)';
                    container.style.opacity = '1';
        
                } catch (error) {
                    console.error('Error loading incidents:', error);
                } finally {
                    this.transitioning = false;
                }
            }
        }"
            class="bg-white p-4 rounded-2xl border border-gray-100 dark:bg-white/5 dark:border-gray-600 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="inline-flex p-1.5 bg-gray-100/80 backdrop-blur-md rounded-2xl shadow-inner dark:bg-white/5">
                    <button @click="handleClick('pendientes')" :class="currentTab === 'pendientes' ? 'bg-white dark:bg-white/10 text-gray-800 dark:text-white shadow-sm' :
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all relative cursor-pointer outline-none focus:outline-none">
                        Pendientes
                        <div x-show="currentTab === 'pendientes'"
                            class="absolute inset-0 rounded-xl bg-white dark:bg-white/10 shadow-sm dark:shadow-none -z-10"
                            layoutId="tab-bg"></div>
                    </button>
                    <button @click="handleClick('historial')" :class="currentTab === 'historial' ? 'bg-white dark:bg-white/10 text-gray-800 dark:text-white shadow-sm' :
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all relative cursor-pointer outline-none focus:outline-none">
                        Historial
                        <div x-show="currentTab === 'historial'"
                            class="absolute inset-0 rounded-xl bg-white dark:bg-white/10 shadow-sm dark:shadow-none -z-10"
                            layoutId="tab-bg"></div>
                    </button>
                </div>
            </div>

            <div class="text-sm text-gray-500">
                <span class="font-bold text-gray-900">{{ $activasCount }}</span> incidencias activas
            </div>
        </div>

        {{-- Incidents List --}}
        <div id="incidencias-list-container" class="space-y-4 transition-all duration-100 ease-in-out">
            @include('incidencias.partials.lista', ['grupos' => $grupos])
        </div>

        <div class="mt-4">
            {{ $grupos->appends(['tab' => 'incidencias'])->onEachSide(1)->links() }}
        </div>

        {{-- Create Modal --}}
        <div id="createModal" tabindex="-1" aria-hidden="true" x-data="{
            machineSearch: '',
            selectedMachine: null,
            openMachineDropdown: false,
        
            selectMachine(id, code, name, image) {
                this.selectedMachine = { id, code, name, image };
                this.machineSearch = `${code} - ${name}`;
                this.openMachineDropdown = false;
            },
        
            onSearchInput() {
                this.openMachineDropdown = true;
                if (this.machineSearch === '') {
                    this.selectedMachine = null;
                }
            },
            previewImage: null,
            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.previewImage = URL.createObjectURL(file);
                }
            }
        }"
            class="hidden fixed inset-0 z-[9999] overflow-y-auto overflow-x-hidden p-4 md:inset-0 h-full max-h-full flex items-center justify-center bg-black/60 backdrop-blur-sm">

            <div class="relative w-full max-w-5xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Modal header -->
                    <div class="flex justify-between items-center p-5 bg-red-600 border-b border-red-700">
                        <div class="flex items-center gap-3 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <h3 class="text-xl font-bold">
                                Reportar Incidencia
                            </h3>
                        </div>
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                            class="text-white/80 bg-transparent hover:bg-white/20 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal body -->
                    <form action="{{ route('incidencias.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="p-8">

                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

                                <!-- Left Column: Photo Upload -->
                                <div class="lg:col-span-2 space-y-2">
                                    <label class="block text-sm font-bold text-gray-700">Evidencia Fotográfica</label>
                                    <label
                                        class="flex flex-col items-center justify-center w-full h-full border-2 border-gray-300 border-dashed rounded-2xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-all relative overflow-hidden group">

                                        <!-- Placeholder / Empty State -->
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-400 group-hover:text-gray-600"
                                            x-show="!previewImage">
                                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <p class="mb-2 text-sm font-semibold">Tocar para tomar foto</p>
                                            <p class="text-xs">Cámara o Galería</p>
                                        </div>

                                        <!-- Preview Image -->
                                        <img x-show="previewImage" :src="previewImage"
                                            class="absolute inset-0 w-full h-full object-cover" />

                                        <input name="imagen" type="file" id="imagenInput" style="display:none;"
                                            accept="image/*" @change="handleFileSelect" />
                                    </label>
                                </div>

                                <!-- Right Column: Form Fields -->
                                <div class="lg:col-span-3 space-y-5">

                                    <!-- Máquina Custom Select -->
                                    <div class="relative">
                                        <label class="block mb-1.5 text-sm font-bold text-gray-700">Máquina
                                            Afectada</label>
                                        <input type="hidden" name="maquina_id" :value="selectedMachine?.id" required>

                                        <div class="relative">
                                            <!-- Search Input Trigger -->
                                            <div class="relative">
                                                <input type="text" x-model="machineSearch" @input="onSearchInput"
                                                    @click="openMachineDropdown = true"
                                                    @click.away="openMachineDropdown = false"
                                                    placeholder="Buscar y seleccionar máquina..."
                                                    class="w-full rounded-xl border border-gray-300 py-3 pl-12 pr-4 text-sm font-medium focus:border-red-500 focus:outline-none focus:ring-transparent shadow-sm placeholder-gray-400"
                                                    autocomplete="off">

                                                <!-- Leading Icon (or Selected Image) -->
                                                <div
                                                    class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <template x-if="selectedMachine && selectedMachine.image">
                                                        <img :src="selectedMachine.image"
                                                            class="h-6 w-6 rounded-full object-cover border border-gray-200">
                                                    </template>
                                                    <template x-if="!selectedMachine || !selectedMachine.image">
                                                        <svg class="h-6 w-6 text-gray-400"
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path
                                                                d="m21.12 6.4-6.05-4.06a2 2 0 0 0-2.17-.05L2.95 8.41a2 2 0 0 0-.95 1.7v5.82a2 2 0 0 0 .88 1.66l6.05 4.07a2 2 0 0 0 2.17.05l9.95-6.12a2 2 0 0 0 .95-1.7V8.06a2 2 0 0 0-.88-1.66Z" />
                                                            <path d="M10 22v-8L2.25 9.15" />
                                                            <path d="m10 14 11.77-6.87" />
                                                        </svg>
                                                    </template>
                                                </div>

                                                <!-- Dropdown Chevron -->
                                                <div
                                                    class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                                                        fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>

                                            <!-- Dropdown List -->
                                            <div x-show="openMachineDropdown"
                                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl bg-white py-1 text-sm shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none">

                                                @foreach ($maquinas as $maquina)
                                                    <div x-show="machineSearch === '' || '{{ strtolower($maquina->nombre . ' ' . $maquina->codigo) }}'.includes(machineSearch.toLowerCase())"
                                                        @click="selectMachine('{{ $maquina->id }}', '{{ $maquina->codigo }}', '{{ $maquina->nombre }}', '{{ $maquina->imagen ? asset($maquina->imagen) : '' }}')"
                                                        class="relative cursor-pointer select-none py-3 pl-3 pr-9 hover:bg-red-50 text-gray-900 group border-b border-gray-50 last:border-0 transition-colors">
                                                        <div class="flex items-center">
                                                            <!-- Icon/Image -->
                                                            <div class="shrink-0 mr-3">
                                                                @if ($maquina->imagen)
                                                                    <img src="{{ asset($maquina->imagen) }}" alt=""
                                                                        class="h-9 w-9 rounded-full object-cover border border-gray-200">
                                                                @else
                                                                    <div
                                                                        class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18"
                                                                            height="18" viewBox="0 0 24 24" fill="none"
                                                                            stroke="currentColor" stroke-width="2"
                                                                            stroke-linecap="round" stroke-linejoin="round">
                                                                            <path
                                                                                d="m21.12 6.4-6.05-4.06a2 2 0 0 0-2.17-.05L2.95 8.41a2 2 0 0 0-.95 1.7v5.82a2 2 0 0 0 .88 1.66l6.05 4.07a2 2 0 0 0 2.17.05l9.95-6.12a2 2 0 0 0 .95-1.7V8.06a2 2 0 0 0-.88-1.66Z" />
                                                                            <path d="M10 22v-8L2.25 9.15" />
                                                                            <path d="m10 14 11.77-6.87" />
                                                                        </svg>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <!-- Text Info -->
                                                            <div class="flex flex-col">
                                                                <div
                                                                    class="font-bold text-gray-800 group-hover:text-red-700">
                                                                    {{ $maquina->nombre }}
                                                                </div>
                                                                <div class="text-xs text-gray-500 flex items-center gap-1">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                                        </path>
                                                                    </svg>
                                                                    <span class="font-medium text-gray-400">|</span>
                                                                    <span>{{ $maquina->obra->obra ?? 'Sin obra asignada' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                <!-- Empty state if no matches -->
                                                <div x-show="machineSearch !== '' && $el.querySelectorAll('div[x-show]').length === 0"
                                                    class="p-3 text-center text-gray-500 text-sm">
                                                    No se encontraron máquinas
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Título -->
                                    <div>
                                        <label for="titulo" class="block mb-1.5 text-sm font-bold text-gray-700">Título
                                            /
                                            Resumen</label>
                                        <input type="text" name="titulo" id="titulo" required autocomplete="off"
                                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-transparent focus:border-red-500 block w-full p-3 shadow-sm placeholder-gray-400"
                                            placeholder="Ej: Ruido en motor, Fuga de aceite...">
                                    </div>

                                    <!-- Descripción -->
                                    <div>
                                        <label for="descripcion"
                                            class="block mb-1.5 text-sm font-bold text-gray-700">Descripción Detallada
                                            <span class="text-gray-400 font-normal">(Opcional)</span></label>
                                        <textarea name="descripcion" id="descripcion" rows="3" autocomplete="off"
                                            class="block p-3 w-full text-sm text-gray-900 bg-white rounded-xl border border-gray-300 focus:ring-transparent focus:border-red-500 shadow-sm placeholder-gray-400 resize-none"
                                            placeholder="Describe el problema..."></textarea>
                                    </div>

                                    <!-- Estado Máquina -->
                                    <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <label for="estado_maquina"
                                            class="block mb-2 text-sm font-bold text-gray-700">Estado de la máquina
                                            tras la
                                            incidencia</label>
                                        <div class="relative">
                                            <select name="estado_maquina" id="estado_maquina"
                                                class="appearance-none bg-white border-2 border-red-500 text-gray-900 text-sm rounded-xl focus:ring-transparent focus:border-red-500 block w-full p-3 pr-10 font-bold shadow-sm">
                                                <option value="averiada" selected>⛔ INOPERATIVA (Averiada)</option>
                                                <option value="activa">✅ OPERATIVA (Incidente menor/seguridad)</option>
                                                <option value="pausa">⏸ EN PAUSA (Temporal)</option>
                                                <option value="mantenimiento">🔧 MANTENIMIENTO (Programado)</option>
                                            </select>
                                            <div
                                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                </path>
                                            </svg>
                                            <span class="text-gray-600"><strong>Inoperativa:</strong> Parada total.
                                                <strong>Operativa:</strong> Incidente leve.</span>
                                        </p>
                                    </div>

                                    <!-- Hidden Priority Default -->
                                    <input type="hidden" name="prioridad" value="media">
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-end p-5 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl gap-3">
                            <button type="button"
                                onclick="document.getElementById('createModal').classList.add('hidden')"
                                class="px-6 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-100 transition-all shadow-sm">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 shadow-lg shadow-red-500/30 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                                Publicar Incidencia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</x-app-layout>