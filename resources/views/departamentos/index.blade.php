<x-app-layout>
    <x-slot name="title">Permisos y configuración</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Permisos y configuración') }}
        </h2>
    </x-slot>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.2);
            border-radius: 20px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.4);
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(129, 140, 248, 0.2);
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(129, 140, 248, 0.4);
        }
    </style>

    <div class="py-4 relative flex flex-col gap-8 md:max-w-7xl md:mx-auto" x-data="{
        openModal: false,
        openModalSecciones: false,
        openModalRutas: false,
        openNuevoDepartamentoModal: false,
        openNuevaSeccionModal: false,
        departamentoId: null,
        departamentoNombre: '',
        usuariosMarcados: [],
        todasLasSecciones: @js($todasLasSecciones),
        rutasDepartamento: [],
        cargandoRutas: false,
        nuevaRuta: { ruta: '', descripcion: '' },
        searchSeccion: '',
        get seccionesFiltradasCount() {
            if (!this.searchSeccion) return this.todasLasSecciones.length;
            const search = this.searchSeccion.toLowerCase();
            return this.todasLasSecciones.filter(s => 
                s.nombre.toLowerCase().includes(search) || 
                (s.ruta && s.ruta.toLowerCase().includes(search))
            ).length;
        },
        agregarSeccionAlModal(seccion) {
            // Agregar la sección al array reactivo con estructura compatible
            this.todasLasSecciones.push({
                id: seccion.id,
                nombre: seccion.nombre,
                ruta: seccion.ruta,
                icono: seccion.icono || null,
                mostrar_en_dashboard: seccion.mostrar_en_dashboard || false,
                departamentos: []
            });
        },
        async cargarRutas(depId, depNombre) {
            this.departamentoId = depId;
            this.departamentoNombre = depNombre;
            this.cargandoRutas = true;
            this.openModalRutas = true;
            try {
                const response = await fetch(`/departamentos/${depId}/rutas`);
                const data = await response.json();
                this.rutasDepartamento = data.rutas || [];
            } catch (error) {
                console.error('Error cargando rutas:', error);
                this.rutasDepartamento = [];
            }
            this.cargandoRutas = false;
        },
        async agregarRuta() {
            if (!this.nuevaRuta.ruta.trim()) return;
            try {
                const response = await fetch(`/departamentos/${this.departamentoId}/rutas/agregar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(this.nuevaRuta)
                });
                const data = await response.json();
                if (data.success) {
                    this.rutasDepartamento.push(data.ruta);
                    this.nuevaRuta = { ruta: '', descripcion: '' };
                } else {
                    alert(data.message || 'Error al agregar ruta');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al agregar ruta');
            }
        },
        async eliminarRuta(rutaId) {
            if (!confirm('¿Eliminar esta ruta?')) return;
            try {
                const response = await fetch(`/departamentos/${this.departamentoId}/rutas/${rutaId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.rutasDepartamento = this.rutasDepartamento.filter(r => r.id !== rutaId);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    }" @seccion-creada.window="agregarSeccionAlModal($event.detail)">

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-green-700 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-red-700 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Tabla resumen de departamentos -->
        <div
            class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
            <div
                class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Resumen Departamentos
                    </h3>
                    <p class="text-blue-100 text-sm mt-1">Configura las áreas de la empresa, sus miembros y accesos
                        específicos</p>
                </div>
                <button @click="openNuevoDepartamentoModal = true"
                    class="w-full sm:w-auto bg-white hover:bg-blue-50 text-blue-600 font-bold py-2.5 px-6 rounded-xl shadow-lg transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Departamento
                </button>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider border-b dark:border-gray-700">
                            <th class="px-6 py-4 text-left font-bold">Departamento</th>
                            <th class="px-6 py-4 text-left font-bold">Descripción</th>
                            <th class="px-6 py-4 text-center font-bold">Usuarios</th>
                            <th class="px-6 py-4 text-center font-bold">Secciones</th>
                            <th class="px-6 py-4 text-center font-bold">Acciones</th>
                        </tr>
                    </thead>
                    @forelse ($departamentos as $departamento)
                        <tbody x-data="{ isExpanded: false }"
                            class="divide-y divide-gray-100 dark:divide-gray-700 border-t-0">
                            <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="isExpanded = !isExpanded"
                                        class="flex items-center gap-3 group focus:outline-none">
                                        <div
                                            class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)] group-hover:scale-125 transition-all duration-300 shrink-0">
                                        </div>
                                        <span
                                            class="font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ $departamento->nombre }}
                                        </span>
                                        <svg :class="{'rotate-180': isExpanded}"
                                            class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400 line-clamp-1">{{ $departamento->descripcion ?: '—' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $departamento->usuarios->count() > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800/50' : 'bg-gray-100 text-gray-500 dark:bg-gray-800/50 dark:text-gray-400' }}">
                                        {{ $departamento->usuarios->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $departamento->secciones->count() > 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50' : 'bg-gray-100 text-gray-500 dark:bg-gray-800/50 dark:text-gray-400' }}">
                                        {{ $departamento->secciones->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click="openModalSecciones = true; departamentoId = {{ $departamento->id }};"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Secciones
                                        </button>
                                        <button @click="cargarRutas({{ $departamento->id }}, '{{ $departamento->nombre }}')"
                                            class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                            Rutas
                                        </button>
                                        <button
                                            @click="openModal = true; departamentoId = {{ $departamento->id }}; usuariosMarcados = {{ $departamento->usuarios->pluck('id') }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            Usuarios
                                        </button>
                                        <a href="{{ route('departamentos.edit', $departamento) }}" wire:navigate
                                            class="inline-flex items-center px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Editar
                                        </a>
                                        <form action="{{ route('departamentos.destroy', $departamento) }}" method="POST"
                                            class="inline-block"
                                            onsubmit="return confirm('¿Está seguro de eliminar este departamento? Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <tr x-show="isExpanded" x-collapse class="bg-gray-50/50 dark:bg-gray-700/20">
                                <td colspan="5" class="px-6 py-6 overflow-hidden">
                                    <div class="max-w-4xl">
                                        @include('departamentos.partials.detalle-departamento', ['departamento' => $departamento])
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody class="bg-white dark:bg-gray-800">
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                        <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <p class="text-lg font-bold">No hay departamentos</p>
                                        <p class="text-sm">Configura tu organización creando el primer departamento</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            <!-- Vista Mobile -->
            <div class="md:hidden space-y-3 p-3 bg-gray-50 dark:bg-gray-900/10">
                @forelse ($departamentos as $departamento)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm bg-white dark:bg-gray-800 overflow-hidden"
                        x-data="{ isExpanded: false }">
                        <!-- Cabecera del card -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/30 border-b dark:border-gray-700">
                            <button @click="isExpanded = !isExpanded" class="w-full text-left focus:outline-none">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <h3
                                            class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                                            <svg :class="{'rotate-90': isExpanded}"
                                                class="w-4 h-4 transition-transform duration-200 text-blue-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                            {{ $departamento->nombre }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1">
                                            {{ $departamento->descripcion ?? 'Sin descripción' }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2 shrink-0">
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100/50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800/50">
                                            {{ $departamento->usuarios->count() }} U
                                        </span>
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100/50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                                            {{ $departamento->secciones->count() }} S
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Botones de Acción Rápidos -->
                        <div class="p-3 bg-white dark:bg-gray-800 grid grid-cols-2 gap-2">
                            <button @click="openModalSecciones = true; departamentoId = {{ $departamento->id }};"
                                class="flex items-center justify-center gap-2 p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-bold transition-all hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Secciones
                            </button>
                            <button @click="cargarRutas({{ $departamento->id }}, '{{ $departamento->nombre }}')"
                                class="flex items-center justify-center gap-2 p-2 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-lg text-xs font-bold transition-all hover:bg-orange-100 dark:hover:bg-orange-900/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Rutas
                            </button>
                            <button
                                @click="openModal = true; departamentoId = {{ $departamento->id }}; usuariosMarcados = {{ $departamento->usuarios->pluck('id') }}"
                                class="flex items-center justify-center gap-2 p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg text-xs font-bold transition-all hover:bg-emerald-100 dark:hover:bg-emerald-900/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Usuarios
                            </button>
                            <a href="{{ route('departamentos.edit', $departamento) }}"
                                class="flex items-center justify-center gap-2 p-2 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-bold transition-all hover:bg-amber-100 dark:hover:bg-amber-900/50 text-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar
                            </a>
                            <form action="{{ route('departamentos.destroy', $departamento) }}" method="POST"
                                class="col-span-2" onsubmit="return confirm('¿Está seguro de eliminar este departamento?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 p-2 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg text-xs font-bold transition-all hover:bg-red-100 dark:hover:bg-red-900/50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Eliminar
                                </button>
                            </form>
                        </div>

                        <!-- Detalles expandibles -->
                        <div x-show="isExpanded" x-collapse
                            class="border-t dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20 p-4 space-y-4">
                            @include('departamentos.partials.detalle-departamento', ['departamento' => $departamento])
                        </div>
                    </div>
                @empty
                    <div
                        class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">No hay departamentos
                            registrados</p>
                    </div>
                @endforelse
            </div>
        </div>





        <!-- Tabla resumen de todas las secciones -->
        <div
            class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
            <div
                class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Resumen Secciones
                    </h3>
                    <p class="text-indigo-100 text-sm mt-1">Gestiona todos los módulos del sistema y su visibilidad
                        global</p>
                </div>
                <button @click="openNuevaSeccionModal = true"
                    class="w-full sm:w-auto bg-white hover:bg-indigo-50 text-indigo-600 font-bold py-2.5 px-6 rounded-xl shadow-lg transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Sección
                </button>
            </div>

            <div
                class="w-full overflow-x-auto overflow-y-auto max-h-[65vh] custom-scrollbar selection:bg-indigo-100 selection:text-indigo-900">
                <table id="tabla-secciones" class="w-full min-w-[800px] border-collapse">
                    <thead class="sticky top-0 z-20 shadow-sm">
                        <tr
                            class="bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-sm text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider border-b dark:border-gray-700">
                            <th class="px-6 py-3 text-left font-bold min-w-[220px]">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                    </div>
                                    <div class="relative group">
                                        <input type="text" x-model="searchSeccion"
                                            placeholder="Buscar por nombre o ruta..."
                                            class="w-full pl-9 pr-8 py-2 text-xs font-semibold bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm placeholder-gray-400 dark:placeholder-gray-500 normal-case"
                                            @click.stop>
                                        <div
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <button x-show="searchSeccion" @click="searchSeccion = ''"
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-all"
                                            title="Limpiar búsqueda">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left font-bold">Ruta</th>
                            <th class="px-6 py-4 text-center font-bold">Icono</th>
                            <th class="px-6 py-4 text-left font-bold">Departamentos asociados</th>
                            <th class="px-6 py-4 text-center font-bold">Pág. Dashboard</th>
                            <th class="px-6 py-4 text-center font-bold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($todasLasSecciones as $sec)
                            <tr data-seccion-id="{{ $sec->id }}"
                                x-data="{
                                                                                                                                                editando: false,
                                                                                                                                                seccion: @js($sec),
                                                                                                                                                original: JSON.parse(JSON.stringify(@js($sec))),
                                                                                                                                                cerrarEdicion() {
                                                                                                                                                    if (this.editando) {
                                                                                                                                                        this.seccion = JSON.parse(JSON.stringify(this.original));
                                                                                                                                                        this.editando = false;
                                                                                                                                                    }
                                                                                                                                                }
                                                                                                                                            }"
                                @cerrar-edicion-sec.window="cerrarEdicion()"
                                @dblclick="$dispatch('cerrar-edicion-sec'); $nextTick(() => editando = true)"
                                @keydown.enter.stop.prevent="guardarSeccion(seccion); editando = false; original = JSON.parse(JSON.stringify(seccion))"
                                @keydown.escape="seccion = JSON.parse(JSON.stringify(original)); editando = false"
                                x-show="!searchSeccion || seccion.nombre.toLowerCase().includes(searchSeccion.toLowerCase()) || (seccion.ruta && seccion.ruta.toLowerCase().includes(searchSeccion.toLowerCase()))"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                :class="{ 'bg-yellow-100/50 dark:bg-yellow-900/20': editando }"
                                class="border-b dark:border-gray-700 last:border-0 cursor-pointer hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 focus:outline-none transition-colors group"
                                tabindex="0">

                                <!-- Nombre -->
                                <td class="px-6 py-4">
                                    <template x-if="!editando">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.5)] group-hover:scale-125 transition-all duration-300 shrink-0">
                                            </div>
                                            <span
                                                class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"
                                                x-text="seccion.nombre"></span>
                                        </div>
                                    </template>
                                    <input x-show="editando" type="text" x-model="seccion.nombre"
                                        class="w-full px-3 py-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                </td>

                                <!-- Ruta -->
                                <td class="px-6 py-4">
                                    <template x-if="!editando">
                                        <code
                                            class="text-xs font-mono bg-gray-100 dark:bg-gray-900/50 px-2 py-1 rounded text-indigo-600 dark:text-indigo-400"
                                            x-text="seccion.ruta"></code>
                                    </template>
                                    <input x-show="editando" type="text" x-model="seccion.ruta"
                                        class="w-full px-3 py-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                </td>

                                <!-- Icono -->
                                <td class="px-6 py-4 text-center">
                                    <template x-if="!editando">
                                        <div class="flex justify-center">
                                            <template x-if="seccion.icono">
                                                <div
                                                    class="p-1.5 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                                    <img :src="'{{ asset('') }}' + seccion.icono" alt="Icono"
                                                        class="h-6 w-6 object-contain">
                                                </div>
                                            </template>
                                            <template x-if="!seccion.icono">
                                                <span class="text-gray-400 dark:text-gray-500 italic text-xs">Sin
                                                    icono</span>
                                            </template>
                                        </div>
                                    </template>
                                    <input x-show="editando" type="text" x-model="seccion.icono"
                                        class="w-full px-3 py-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                </td>

                                <!-- Departamentos asociados -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @php
                                            $deps = $sec->departamentos->pluck('nombre');
                                        @endphp
                                        @forelse($deps as $dName)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                                                {{ $dName }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400 dark:text-gray-500 italic text-xs">Ninguno</span>
                                        @endforelse
                                    </div>
                                </td>

                                <!-- Mostrar en dashboard -->
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center">
                                        <label class="relative inline-flex items-center cursor-pointer group">
                                            <input type="checkbox" :checked="seccion.mostrar_en_dashboard"
                                                @change="seccion.mostrar_en_dashboard = $event.target.checked; toggleMostrarDashboard(seccion.id, $event.target.checked)"
                                                class="sr-only peer">
                                            <div
                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600">
                                            </div>
                                        </label>
                                    </div>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                            @click.stop="eliminarSeccion({{ $sec->id }}, '{{ $sec->nombre }}')"
                                            class="p-2 text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                            title="Eliminar sección">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        @endforeach

                        <!-- Estado vacío para la búsqueda -->
                        <tr x-show="seccionesFiltradasCount === 0" x-cloak>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <div class="p-4 bg-gray-100 dark:bg-gray-700/50 rounded-full mb-4">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0M10 11V13M10 17H10.01" />
                                        </svg>
                                    </div>
                                    <p class="text-lg font-bold">No se encontraron resultados</p>
                                    <p class="text-sm">No hay secciones que coincidan con "<span x-text="searchSeccion"
                                            class="font-medium text-indigo-600 dark:text-indigo-400"></span>"</p>
                                    <button @click="searchSeccion = ''"
                                        class="mt-4 text-indigo-600 dark:text-indigo-400 hover:underline font-medium text-sm">
                                        Limpiar búsqueda
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>
        <!-- ═══════════════════════════════════════════════════════════════════════════════
             AUTO-DETECCIÓN DE SECCIONES - Módulos sin configurar
        ═══════════════════════════════════════════════════════════════════════════════ -->
        @if(count($seccionesComparacion['sin_seccion']) > 0)
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-amber-300 dark:border-amber-800/50"
                x-data="seccionesAutoDetect()" x-show="seccionesFaltantes.length > 0"
                x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Módulos Sin Sección
                            </h3>
                            <p class="text-amber-100 text-sm mt-1">
                                <span x-text="seccionesFaltantes.length"></span> módulos detectados que los usuarios de
                                oficina NO pueden acceder
                            </p>
                        </div>
                        <button @click="sincronizarTodas()" :disabled="sincronizando || seccionesFaltantes.length === 0"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-lg transition disabled:opacity-50">
                            <svg x-show="!sincronizando" class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg x-show="sincronizando" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span
                                x-text="sincronizando ? 'Creando...' : 'Crear Todas (' + seccionesFaltantes.length + ')'"></span>
                        </button>
                    </div>
                </div>

                <div class="p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                        <template x-for="item in seccionesFaltantes" :key="item.prefijo">
                            <div
                                class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/20 transition">
                                <div class="flex-1 min-w-0 mr-2">
                                    <div class="font-medium text-gray-900 dark:text-gray-200 truncate"
                                        x-text="item.nombre_sugerido"></div>
                                    <div class="text-xs text-gray-500 font-mono">
                                        <span x-text="item.prefijo + '.*'"></span>
                                        <span class="text-amber-600" x-text="'(' + item.total_rutas + ')'"></span>
                                    </div>
                                </div>
                                <button @click="crearSeccion(item.prefijo, item.nombre_sugerido)"
                                    :disabled="creandoPrefijo === item.prefijo"
                                    class="shrink-0 inline-flex items-center px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="creandoPrefijo === item.prefijo" class="animate-spin w-4 h-4" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg x-show="creandoPrefijo !== item.prefijo" class="w-4 h-4" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/50 rounded-lg text-sm text-blue-700 dark:text-blue-300"
                        x-show="seccionesFaltantes.length > 0">
                        <strong>Siguiente paso:</strong> Después de crear las secciones, asígnalas a los departamentos
                        correspondientes usando el botón "Secciones" de cada departamento.
                    </div>

                    <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800/50 rounded-lg text-center"
                        x-show="seccionesFaltantes.length === 0" x-cloak>
                        <svg class="w-12 h-12 mx-auto text-green-500 dark:text-green-400 mb-2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-green-700 dark:text-green-400 font-medium">Todos los módulos tienen sección
                            asignada</p>
                    </div>
                </div>
            </div>


        @endif

        <!-- ═══════════════════════════════════════════════════════════════════════════════
             CONFIGURACIÓN DEL DASHBOARD - Orden de secciones y acceso de operarios
        ═══════════════════════════════════════════════════════════════════════════════ -->
        <div
            class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuración del Dashboard
                </h3>
                <p class="text-orange-100 text-sm mt-1">Arrastra las secciones para cambiar el orden en el dashboard
                </p>
            </div>

            <div class="p-6">
                <!-- Secciones del Dashboard con Drag & Drop -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Orden de Secciones en Dashboard
                        <span class="text-sm font-normal text-gray-500">(arrastra para reordenar)</span>
                    </h4>

                    <div id="sortable-secciones" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($seccionesDashboard as $seccion)
                            <div class="seccion-item flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-move hover:border-orange-400 dark:hover:border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-500/10 transition-all group"
                                data-id="{{ $seccion->id }}">
                                <div class="drag-handle text-gray-400 group-hover:text-orange-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 8h16M4 16h16" />
                                    </svg>
                                </div>
                                <span
                                    class="w-8 h-8 flex items-center justify-center bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 rounded-lg font-bold text-sm orden-numero">
                                    {{ $loop->iteration }}
                                </span>
                                @if ($seccion->icono)
                                    <img src="{{ asset($seccion->icono) }}" alt="" class="w-6 h-6">
                                @else
                                    <div
                                        class="w-6 h-6 bg-gradient-to-br from-orange-400 to-amber-500 rounded flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($seccion->nombre, 0, 1)) }}
                                    </div>
                                @endif
                                <span
                                    class="font-medium text-gray-700 dark:text-gray-200 flex-1">{{ $seccion->nombre }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $seccion->ruta }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <button type="button" id="btn-guardar-orden"
                            class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Guardar Orden
                        </button>
                        <span id="orden-status" class="text-sm text-gray-500 hidden">
                            <svg class="w-4 h-4 inline-block animate-spin" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Guardando...
                        </span>
                    </div>
                </div>

                <!-- Configuración de Secciones para Operarios -->
                @if ($departamentoOperarios)
                    <div class="border-t dark:border-gray-700 pt-6 mt-6">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Secciones visibles para Operarios
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(Departamento:
                                {{ $departamentoOperarios->nombre }})</span>
                        </h4>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Las secciones marcadas serán visibles en el dashboard de los usuarios con rol "operario".
                            <a href="#"
                                @click.prevent="openModalSecciones = true; departamentoId = {{ $departamentoOperarios->id }}"
                                class="text-blue-600 dark:text-blue-400 hover:underline">Editar secciones</a>
                        </p>

                        <div class="flex flex-wrap gap-2">
                            @forelse ($departamentoOperarios->secciones as $seccion)
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 rounded-full text-sm font-medium">
                                    @if ($seccion->icono)
                                        <img src="{{ asset($seccion->icono) }}" alt="" class="w-4 h-4">
                                    @endif
                                    {{ $seccion->nombre }}
                                </span>
                            @empty
                                <span class="text-gray-500 dark:text-gray-400 italic">No hay secciones asignadas al
                                    departamento
                                    {{ $departamentoOperarios->nombre }}</span>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="border-t dark:border-gray-700 pt-6 mt-6">
                        <div
                            class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/50 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-500 mt-0.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h5 class="font-semibold text-yellow-800 dark:text-yellow-500">Departamento
                                        "Operario" no encontrado
                                    </h5>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-400/80 mt-1">
                                        Para configurar las secciones visibles para operarios, primero crea un
                                        departamento llamado exactamente "Operario".
                                    </p>
                                    <button type="button" @click="openNuevoDepartamentoModal = true"
                                        class="mt-2 text-sm text-yellow-800 dark:text-yellow-500 hover:text-yellow-900 dark:hover:text-yellow-400 font-medium underline">
                                        + Crear departamento Operario
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════════════
             CONFIGURACIÓN DE ALERTAS DE AVERÍAS
        ═══════════════════════════════════════════════════════════════════════════════ -->
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 5px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
                margin: 4px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(156, 163, 175, 0.5);
                border-radius: 10px;
            }

            .dark .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(156, 163, 175, 0.3);
            }

            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgba(156, 163, 175, 0.8);
            }
        </style>

        <div
            class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="bg-gradient-to-r from-red-500 to-rose-500 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Configuración de Alertas de Averías
                </h3>
                <p class="text-red-100 text-sm mt-1">Define quién recibirá las notificaciones cuando se reporte una
                    incidencia en máquinas.</p>
            </div>

            <div class="p-6">
                <form action="{{ route('departamentos.updateAlertSettings') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Roles -->
                        <div>
                            <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Por Rol
                            </h4>
                            <div
                                class="space-y-1 bg-gray-50 dark:bg-black/20 p-4 pr-2 rounded-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto custom-scrollbar">
                                @foreach ($roles as $rol)
                                    <label
                                        class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/50 p-1 rounded">
                                        <input type="checkbox" name="roles[]" value="{{ $rol }}"
                                            class="text-red-600 rounded focus:ring-transparent dark:bg-gray-700 dark:border-gray-600"
                                            {{ in_array($rol, $alertasConfig['roles'] ?? []) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 uppercase">{{ $rol }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Departamentos -->
                        <div>
                            <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Por Departamento
                            </h4>
                            <div
                                class="space-y-1 bg-gray-50 dark:bg-black/20 p-4 pr-2 rounded-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto custom-scrollbar">
                                @foreach ($departamentos as $dep)
                                    <label
                                        class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/50 p-1 rounded">
                                        <input type="checkbox" name="departamentos[]" value="{{ $dep->nombre }}"
                                            class="text-red-600 rounded focus:ring-transparent dark:bg-gray-700 dark:border-gray-600"
                                            {{ in_array($dep->nombre, $alertasConfig['departamentos'] ?? []) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $dep->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Usuarios Específicos -->
                        <div>
                            <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Usuarios Específicos
                            </h4>
                            <div
                                class="space-y-1 bg-gray-50 dark:bg-black/20 p-4 pr-2 rounded-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto custom-scrollbar">
                                <input type="text" placeholder="Buscar usuario..."
                                    class="w-full text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded mb-2 focus:border-red-500 focus:ring-transparent"
                                    onkeyup="filtrarUsuarios(this)">
                                <div id="lista-usuarios-alertas">
                                    @foreach ($todosUsuarios as $usuario)
                                        <label
                                            class="flex items-center gap-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/50 p-1 rounded usuario-item">
                                            <input type="checkbox" name="usuarios[]" value="{{ $usuario->id }}"
                                                class="text-red-600 rounded focus:ring-transparent dark:bg-gray-700 dark:border-gray-600"
                                                {{ in_array($usuario->id, $alertasConfig['usuarios'] ?? []) ? 'checked' : '' }}>
                                            <img src="{{ $usuario->ruta_imagen }}" class="w-8 h-8 rounded-full object-cover"
                                                alt=""
                                                onerror="this.onerror=null; this.outerHTML=`<svg xmlns='' width='28' height='28'
                                                                                                                                                                viewBox='0 0 24 24' fill='none' stroke='currentColor'
                                                                                                                                                                stroke-width='1' stroke-linecap='round' stroke-linejoin='round'
                                                                                                                                                                class='lucide lucide-circle-user-round-icon lucide-circle-user-round w-8 h-8 text-neutral-800 dark:text-gray-200'>
                                                                                                                                                                <path d='M18 20a6 6 0 0 0-12 0' />
                                                                                                                                                                <circle cx='12' cy='10' r='4' />
                                                                                                                                                                <circle cx='12' cy='12' r='10' />
                                                                                                                                                            </svg>`" />


                                            <div class="text-xs">
                                                <div class="font-medium text-gray-800 dark:text-gray-200">
                                                    {{ $usuario->name }}
                                                    {{ $usuario->primer_apellido }} {{ $usuario->segundo_apellido }}
                                                </div>
                                                <div class="text-gray-500 dark:text-gray-400">{{ $usuario->rol }}</div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg flex items-center gap-2 transition-all transform hover:scale-[1.01] duration-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            Guardar Configuración de Alertas
                        </button>
                    </div>
                </form>
            </div>
        </div>



        <!-- ═══════════════════════════════════════════════════════════════════════════════
             CONFIGURACIÓN DE ALERTAS DE APROBACIÓN DE PLANILLAS
        ═══════════════════════════════════════════════════════════════════════════════ -->
        @include('departamentos.partials.config-alertas-planilla')

        <!-- Script para Drag & Drop con SortableJS -->
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            // === FUNCIONES GLOBALES ===
            window.guardarDepartamento = function (departamento) {
                fetch(`{{ url('/departamentos') }}/${departamento.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        nombre: departamento.nombre,
                        descripcion: departamento.descripcion
                    })
                })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            let errorMsg = data.message || "Error al actualizar.";
                            if (data.errors) errorMsg = Object.values(data.errors).flat().join("<br>");
                            throw new Error(errorMsg);
                        }
                    })
                    .catch(error => {
                        window.mostrarErrorConReporte(error.message);
                    });
            };

            window.eliminarDepartamento = function (id, nombre) {
                Swal.fire({
                    title: '¿Eliminar departamento?',
                    html: `¿Estás seguro de eliminar el departamento <strong>${nombre}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Sí, eliminar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`{{ url('/departamentos') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const fila = document.querySelector(`tr[data-departamento-id="${id}"]`);
                                    if (fila) fila.remove();
                                    Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1500, showConfirmButton: false });
                                } else {
                                    window.mostrarErrorConReporte(data.message);
                                }
                            })
                            .catch(() => window.mostrarErrorConReporte('Error de conexión'));
                    }
                });
            };

            window.filtrarUsuarios = function (input) {
                const filtro = input.value.toLowerCase();
                document.querySelectorAll('#lista-usuarios-alertas .usuario-item').forEach(item => {
                    item.style.display = item.innerText.toLowerCase().includes(filtro) ? 'flex' : 'none';
                });
            };

            window.seccionesAutoDetect = function () {
                return {
                    sincronizando: false,
                    creandoPrefijo: null,
                    seccionesFaltantes: @json($seccionesComparacion['sin_seccion']),
                    async sincronizarTodas() {
                        if (!confirm(`¿Crear todas las ${this.seccionesFaltantes.length} secciones faltantes?`)) return;
                        this.sincronizando = true;
                        try {
                            const response = await fetch('{{ route("secciones.sincronizarTodas") }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            const data = await response.json();
                            if (data.success) {
                                data.creadas.forEach(s => this.agregarSeccionATabla(s));
                                this.seccionesFaltantes = [];
                                Swal.fire({ icon: 'success', title: 'Éxito', text: data.message, timer: 1500 });
                            }
                        } catch (e) { window.mostrarErrorConReporte(e.message || 'Error al sincronizar'); }
                        finally { this.sincronizando = false; }
                    },
                    async crearSeccion(prefijo, nombre) {
                        this.creandoPrefijo = prefijo;
                        try {
                            const response = await fetch('{{ route("secciones.crearParaPrefijo") }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ prefijo, nombre })
                            });
                            const data = await response.json();
                            if (data.success) {
                                this.seccionesFaltantes = this.seccionesFaltantes.filter(s => s.prefijo !== prefijo);
                                this.agregarSeccionATabla(data.seccion);
                            }
                        } catch (e) { window.mostrarErrorConReporte(e.message || 'Error al crear sección'); }
                        finally { this.creandoPrefijo = null; }
                    },
                    agregarSeccionATabla(seccion) {
                        window.dispatchEvent(new CustomEvent('seccion-creada', { detail: seccion }));
                        // Recargar página o manipular DOM si es necesario. Por ahora usamos el evento de Alpine.
                    }
                };
            };

            window.toggleMostrarDashboard = function (seccionId, valor) {
                fetch(`{{ url('/secciones') }}/${seccionId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ mostrar_en_dashboard: valor ? 1 : 0 })
                })
                    .then(r => r.json())
                    .then(data => { if (!data.success) throw new Error(data.message); })
                    .catch(e => window.mostrarErrorConReporte(e.message));
            };

            window.guardarSeccion = function (seccion) {
                fetch(`{{ url('/secciones') }}/${seccion.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({
                        nombre: seccion.nombre,
                        ruta: seccion.ruta,
                        icono: seccion.icono,
                        mostrar_en_dashboard: seccion.mostrar_en_dashboard ? 1 : 0
                    })
                });
            };

            window.eliminarSeccion = function (id, nombre) {
                Swal.fire({
                    title: '¿Eliminar sección?',
                    html: `¿Eliminar <strong>${nombre}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`{{ url('/secciones') }}/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    document.querySelector(`tr[data-seccion-id="${id}"]`)?.remove();
                                    Swal.fire({ icon: 'success', title: 'Eliminada', timer: 1500 });
                                }
                            });
                    }
                });
            };

            // === INICIALIZACIÓN DE PÁGINA ===
            function initDepartamentosPage() {

                // Reset flag explicitly on each navigation to allow re-init if the element exists
                const sortableContainer = document.getElementById('sortable-secciones');
                if (sortableContainer && !sortableContainer.dataset.sortableInit) {
                    new Sortable(sortableContainer, {
                        animation: 150,
                        handle: '.seccion-item',
                        onEnd: function () {
                            document.querySelectorAll('.seccion-item').forEach((item, index) => {
                                item.querySelector('.orden-numero').textContent = index + 1;
                            });
                        }
                    });
                    sortableContainer.dataset.sortableInit = 'true';
                }

                const btnGuardar = document.getElementById('btn-guardar-orden');
                if (btnGuardar) {
                    const newBtn = btnGuardar.cloneNode(true);
                    btnGuardar.replaceWith(newBtn);
                    newBtn.addEventListener('click', function () {
                        const orden = Array.from(document.querySelectorAll('.seccion-item')).map(item => parseInt(item.dataset.id));
                        const statusEl = document.getElementById('orden-status');
                        statusEl?.classList.remove('hidden');
                        this.disabled = true;

                        fetch('{{ route('secciones.actualizarOrden') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ orden: orden })
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) Swal.fire({ icon: 'success', title: 'Orden guardado', timer: 2000 });
                            })
                            .finally(() => {
                                statusEl?.classList.add('hidden');
                                this.disabled = false;
                            });
                    });
                }

                // Registrar eventos
                document.addEventListener('livewire:navigated', initDepartamentosPage);
                document.addEventListener('DOMContentLoaded', initDepartamentosPage);

                // Ejecución inmediata por si acaso (para carga inicial sin Livewire o si ya cargó)
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    initDepartamentosPage();
                }
        </script>

        <!-- Modal Nueva Sección -->
        <template x-if="openNuevaSeccionModal">
            <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4">
                <div
                    class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow-lg w-full max-w-md z-50 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Crear nueva sección</h3>

                    <form method="POST" action="{{ route('secciones.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                            <input type="text" name="nombre" required
                                class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ruta (route
                                name)</label>
                            <input type="text" name="ruta" required
                                class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: productos.index">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ruta del
                                icono</label>
                            <input type="text" name="icono"
                                class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="imagenes/iconos/nombre.png">
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openNuevaSeccionModal = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded transition-colors">
                                Crear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Asignar Usuarios -->
        <template x-if="openModal">
            <div
                class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col">
                    <!-- Header del modal -->
                    <div
                        class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Asignar Usuarios</h3>
                                <p class="text-green-100 text-sm">Selecciona los usuarios para este departamento</p>
                            </div>
                        </div>
                        <button @click="openModal = false"
                            class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body del modal -->
                    <form method="POST" :action="'/departamentos/' + departamentoId + '/asignar-usuarios'"
                        class="flex flex-col flex-1 overflow-hidden">
                        @csrf

                        <div class="flex-1 overflow-y-auto p-6 pr-4 custom-scrollbar">
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-semibold" x-text="{{ count($usuariosOficina) }}"></span>
                                        usuarios
                                        disponibles
                                    </p>
                                    <button type="button"
                                        @click="$el.closest('form').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = !cb.checked)"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 font-medium">
                                        Invertir selección
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @forelse ($usuariosOficina as $usuario)
                                    <label
                                        class="flex items-start space-x-3 p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-green-400 dark:hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all cursor-pointer group">
                                        <input type="checkbox" name="usuarios[]" :value="'{{ $usuario->id }}'"
                                            :checked="usuariosMarcados.includes({{ $usuario->id }})"
                                            class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-green-600 dark:bg-gray-700 focus:ring-green-500 dark:focus:ring-offset-gray-800 focus:ring-2 cursor-pointer">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white font-semibold text-sm">
                                                    {{ strtoupper(substr($usuario->nombre_completo, 0, 1)) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">
                                                        {{ $usuario->nombre_completo }}
                                                    </p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                        {{ $usuario->email }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <div class="col-span-2 text-center py-12">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">No hay usuarios con rol
                                            oficina</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Footer del modal -->
                        <div
                            class="border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-between items-center gap-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span
                                    x-text="$el.closest('form').querySelectorAll('input[type=checkbox]:checked').length"></span>
                                seleccionado(s)
                            </p>
                            <div class="flex gap-3">
                                <button type="button" @click="openModal = false"
                                    class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Asignar Usuarios
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Nuevo Departamento -->
        <template x-if="openNuevoDepartamentoModal">
            <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4">
                <div
                    class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow-lg w-full max-w-md z-50 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Crear nuevo departamento
                    </h3>

                    <form method="POST" action="{{ route('departamentos.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                            <input type="text" name="nombre" required
                                class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mb-4">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                            <textarea name="descripcion" rows="3"
                                class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openNuevoDepartamentoModal = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition-colors">
                                Crear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        <!-- Modal Asignar Secciones -->
        <template x-if="openModalSecciones">
            <div
                class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[85vh] overflow-hidden flex flex-col">
                    <!-- Header del modal -->
                    <div
                        class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Asignar Secciones</h3>
                                <p class="text-indigo-100 text-sm">Selecciona las secciones visibles para este
                                    departamento</p>
                            </div>
                        </div>
                        <button @click="openModalSecciones = false"
                            class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body del modal -->
                    <form method="POST" :action="'/departamentos/' + departamentoId + '/asignar-secciones'"
                        class="flex flex-col flex-1 overflow-hidden">
                        @csrf

                        <div class="flex-1 overflow-y-auto p-6 pr-4 custom-scrollbar">
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-semibold" x-text="todasLasSecciones.length"></span>
                                        secciones
                                        disponibles
                                    </p>
                                    <button type="button"
                                        @click="$el.closest('form').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = !cb.checked)"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium">
                                        Invertir selección
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <template x-for="seccion in todasLasSecciones" :key="seccion.id">
                                    <label
                                        class="flex items-start space-x-3 p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all cursor-pointer group">
                                        <input type="checkbox" name="secciones[]" :value="seccion.id"
                                            :checked="(seccion.departamentos || []).map(d => d.id).includes(departamentoId)"
                                            class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 focus:ring-indigo-500 focus:ring-2 cursor-pointer">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start gap-2">
                                                <template x-if="seccion.icono">
                                                    <img :src="'{{ asset('') }}' + seccion.icono" :alt="seccion.nombre"
                                                        class="w-8 h-8 rounded-lg">
                                                </template>
                                                <template x-if="!seccion.icono">
                                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-semibold text-xs"
                                                        x-text="seccion.nombre.substring(0, 2).toUpperCase()">
                                                    </div>
                                                </template>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors"
                                                        x-text="seccion.nombre">
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                                        x-text="seccion.ruta"></p>
                                                    <span x-show="seccion.mostrar_en_dashboard"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 mt-1">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        En dashboard
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                                <!-- Empty state -->
                                <div x-show="todasLasSecciones.length === 0" class="col-span-3 text-center py-12">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">No hay secciones
                                        registradas</p>
                                    <button type="button"
                                        @click="openModalSecciones = false; openNuevaSeccionModal = true"
                                        class="mt-3 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium text-sm">
                                        + Crear primera sección
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Footer del modal -->
                        <div
                            class="border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-between items-center gap-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span
                                    x-text="$el.closest('form').querySelectorAll('input[type=checkbox]:checked').length"></span>
                                seleccionada(s)
                            </p>
                            <div class="flex gap-3">
                                <button type="button" @click="openModalSecciones = false"
                                    class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Asignar Secciones
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Gestionar Rutas -->
        <template x-if="openModalRutas">
            <div
                class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
                    <!-- Header del modal -->
                    <div
                        class="bg-gradient-to-r from-orange-500 to-amber-600 px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Rutas Permitidas</h3>
                                <p class="text-orange-100 text-sm">Departamento: <span
                                        x-text="departamentoNombre"></span></p>
                            </div>
                        </div>
                        <button @click="openModalRutas = false"
                            class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 pr-4 custom-scrollbar">
                        <!-- Formulario para agregar nueva ruta -->
                        <div
                            class="mb-6 p-4 bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800/50 rounded-xl">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Agregar nueva ruta</h4>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <input type="text" x-model="nuevaRuta.ruta"
                                        placeholder="Ej: usuarios.* o vacaciones.eliminarSolicitud"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
                                        @keydown.enter.prevent="agregarRuta()">
                                </div>
                                <div class="flex-1">
                                    <input type="text" x-model="nuevaRuta.descripcion"
                                        placeholder="Descripción (opcional)"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
                                        @keydown.enter.prevent="agregarRuta()">
                                </div>
                                <button @click="agregarRuta()"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors whitespace-nowrap">
                                    + Agregar
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Usa <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">.*</code> al final para
                                permitir todas las subrutas. Ej: <code
                                    class="bg-gray-200 dark:bg-gray-700 px-1 rounded">usuarios.*</code>
                            </p>
                        </div>

                        <!-- Lista de rutas -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200">Rutas configuradas</h4>
                                <span class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="rutasDepartamento.length + ' ruta(s)'"></span>
                            </div>

                            <!-- Loading state -->
                            <div x-show="cargandoRutas" class="text-center py-8">
                                <svg class="animate-spin h-8 w-8 text-orange-500 mx-auto" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <p class="mt-2 text-gray-500">Cargando rutas...</p>
                            </div>

                            <!-- Empty state -->
                            <div x-show="!cargandoRutas && rutasDepartamento.length === 0"
                                class="text-center py-8 bg-gray-50 dark:bg-gray-900/30 rounded-xl">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">No hay rutas configuradas
                                </p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Los usuarios de este
                                    departamento no tendrán acceso especial</p>
                            </div>

                            <!-- Lista de rutas -->
                            <template x-for="ruta in rutasDepartamento" :key="ruta.id">
                                <div
                                    class="flex items-center justify-between p-3 bg-white dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-orange-300 dark:hover:border-orange-500 transition-colors group">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <code
                                                class="text-sm font-mono text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/30 px-2 py-0.5 rounded"
                                                x-text="ruta.ruta"></code>
                                            <span x-show="ruta.ruta.endsWith('.*')"
                                                class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-2 py-0.5 rounded-full">Prefijo</span>
                                        </div>
                                        <p x-show="ruta.descripcion"
                                            class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                            x-text="ruta.descripcion"></p>
                                    </div>
                                    <button @click="eliminarRuta(ruta.id)"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer del modal -->
                    <div
                        class="border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end">
                        <button type="button" @click="openModalRutas = false"
                            class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>