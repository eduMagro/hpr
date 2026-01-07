<x-app-layout>
    <x-slot name="title">Nueva Incidencia - {{ config('app.name') }}</x-slot>

    <div class="px-4 py-6 max-w-3xl mx-auto">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('incidencias.index') }}"
                class="hover:text-blue-600 transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Cancelar y Volver
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-8 py-6">
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Reportar Incidencia
                </h1>
                <p class="text-red-100 mt-2 opacity-90">Completa los detalles para registrar una nueva avería o fallo.
                </p>
            </div>

            <form action="{{ route('incidencias.store') }}" method="POST" enctype="multipart/form-data"
                class="p-8 space-y-6">
                @csrf

                {{-- Machine Selection --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Máquina Afectada <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <select name="maquina_id"
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-red-500 focus:border-red-500 block p-3 pr-10 appearance-none font-medium"
                            required>
                            <option value="">Selecciona una máquina...</option>
                            @foreach ($maquinas as $m)
                                <option value="{{ $m->id }}"
                                    {{ (old('maquina_id') ?? $maquina_id) == $m->id ? 'selected' : '' }}>
                                    {{ $m->codigo }} — {{ $m->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Título del Problema <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}"
                        class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-red-500 focus:border-red-500 block p-3 placeholder-gray-400"
                        placeholder="Ej: Ruido fuerte en motor, Fuga de aceite, Pantalla en negro..." required>
                </div>

                {{-- Priority --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Prioridad</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="prioridad" value="baja" class="peer sr-only">
                            <div
                                class="rounded-xl border border-gray-200 bg-white p-3 text-center transition-all peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 hover:bg-gray-50">
                                <div class="text-sm font-bold">Baja</div>
                                <div class="text-xs text-gray-500 mt-1">No urge</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="prioridad" value="media" class="peer sr-only" checked>
                            <div
                                class="rounded-xl border border-gray-200 bg-white p-3 text-center transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 hover:bg-gray-50">
                                <div class="text-sm font-bold">Media</div>
                                <div class="text-xs text-gray-500 mt-1">Estándar</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="prioridad" value="alta" class="peer sr-only">
                            <div
                                class="rounded-xl border border-gray-200 bg-white p-3 text-center transition-all peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 hover:bg-gray-50">
                                <div class="text-sm font-bold">Alta</div>
                                <div class="text-xs text-gray-500 mt-1">Parada crítica</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descripción Detallada <span
                            class="text-red-500">*</span></label>
                    <textarea name="descripcion" rows="4"
                        class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-red-500 focus:border-red-500 block p-3 placeholder-gray-400"
                        placeholder="Describe qué estaba haciendo la máquina cuando ocurrió el fallo, mensajes de error, ruidos, etc."
                        required>{{ old('descripcion') }}</textarea>
                </div>

                {{-- Photos --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Evidencia (Fotos)</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="dropzone-file"
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-xs text-gray-500"><span class="font-semibold">Click para subir</span> o
                                    arrastra imágenes</p>
                                <p class="text-[10px] text-gray-400 mt-1">PNG, JPG (MAX. 3)</p>
                            </div>
                            <input id="dropzone-file" type="file" name="fotos[]" multiple accept="image/*"
                                class="hidden" onchange="previewFiles(this)" />
                        </label>
                    </div>
                    <div id="preview-container" class="grid grid-cols-3 gap-4 mt-4"></div>
                </div>

                <div class="pt-6 flex items-center justify-end gap-4 border-t border-gray-100">
                    <a href="{{ route('incidencias.index') }}"
                        class="text-gray-600 font-bold text-sm hover:text-gray-800 px-4">Cancelar</a>
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-red-500/20 transform hover:-translate-y-0.5 transition-all">
                        Registrar Incidencia
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function previewFiles(input) {
            const container = document.getElementById('preview-container');
            container.innerHTML = '';

            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className =
                            'relative aspect-square rounded-lg overflow-hidden border border-gray-200';
                        div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                        container.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
</x-app-layout>
