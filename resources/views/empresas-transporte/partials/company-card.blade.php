<div x-data="{ openCamionModal: false }"
    class="glass-card rounded-[2.5rem] overflow-hidden hover-lift group flex flex-col h-full animate-fade-in">
    <div class="p-8 flex-grow">
        <!-- Card Top -->
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center gap-4">
                <div
                    class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-xl font-bold font-outfit shadow-lg shadow-indigo-200">
                    {{ strtoupper(substr($empresa->nombre, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors font-outfit editable"
                        contenteditable="true" data-id="{{ $empresa->id }}" data-field="nombre">
                        {{ $empresa->nombre }}
                    </h2>
                    <p class="text-slate-400 text-xs flex items-center gap-1 mt-1">
                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                        <span class="editable" contenteditable="true" data-id="{{ $empresa->id }}"
                            data-field="direccion">
                            {{ $empresa->direccion }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Contact Quick Info -->
        <div class="space-y-3 mb-8">
            <div class="flex items-center gap-3 text-slate-600 group/item">
                <div
                    class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center group-hover/item:bg-indigo-50 transition-colors">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-medium editable" contenteditable="true" data-id="{{ $empresa->id }}"
                    data-field="telefono">
                    {{ $empresa->telefono }}
                </span>
            </div>
            <div class="flex items-center gap-3 text-slate-600 group/item">
                <div
                    class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center group-hover/item:bg-indigo-50 transition-colors">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-medium editable" contenteditable="true" data-id="{{ $empresa->id }}"
                    data-field="email">
                    {{ $empresa->email }}
                </span>
            </div>
        </div>

        <!-- Fleet List -->
        <div class="bg-slate-50/50 rounded-3xl p-5 border border-slate-100 mb-6">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Listado de Flota</span>
                <span id="trucks-count-{{ $empresa->id }}"
                    class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">
                    {{ $empresa->camiones->count() }} Camiones
                </span>
            </div>

            <div id="empty-trucks-{{ $empresa->id }}" class="{{ $empresa->camiones->isEmpty() ? '' : 'hidden' }}">
                <p class="text-xs text-slate-400 italic text-center py-2">No hay camiones registrados</p>
            </div>

            <div id="trucks-list-{{ $empresa->id }}"
                class="space-y-3 overflow-y-auto max-h-72 custom-scrollbar pr-2 {{ $empresa->camiones->isEmpty() ? 'hidden' : '' }}">
                @foreach ($empresa->camiones as $camion)
                    @include('empresas-transporte.partials.truck-item', ['camion' => $camion])
                @endforeach
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="p-8 pt-0 flex gap-3">
        <button @click="openCamionModal = true"
            class="flex-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 py-3 rounded-2xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Añadir Camión
        </button>
    </div>

    <!-- Modal para añadir camión -->
    <div x-show="openCamionModal" @close-camion-modal.window="openCamionModal = false" x-transition x-cloak
        class="fixed inset-0 flex items-center justify-center z-[60] bg-slate-900/80 backdrop-blur-sm p-4">
        <div @click.away="openCamionModal = false" class="bg-white p-8 rounded-[2.5rem] w-full max-w-md">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 font-outfit">Añadir Camión</h2>
            <form action="{{ route('camiones.store') }}" method="POST" class="space-y-5 form-nuevo-camion">
                @csrf
                <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Modelo /
                        Matrícula</label>
                    <input type="text" name="modelo" placeholder="Ej: Mercedes Actros - 1234 ABC"
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                        required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Capacidad
                        (kg)</label>
                    <input type="number" name="capacidad" placeholder="Ej: 24000"
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                        required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Estado</label>
                    <select name="estado"
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none"
                        required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="openCamionModal = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-4 rounded-2xl font-bold transition-all">Cancelar</button>
                    <button type="submit"
                        class="flex-1 bg-slate-900 hover:bg-black text-white py-4 rounded-2xl font-bold shadow-lg transition-all">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
