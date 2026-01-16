<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Secciones
            </h2>
        </div>
    </x-slot>

    <div class="py-6" x-data="seccionesManager()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Estadísticas --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Prefijos Detectados</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $estadisticas['total_prefijos'] }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Con Sección</div>
                    <div class="text-2xl font-bold text-green-600">{{ $estadisticas['con_seccion'] }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Sin Sección</div>
                    <div class="text-2xl font-bold {{ $estadisticas['sin_seccion'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ $estadisticas['sin_seccion'] }}
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Cobertura</div>
                    <div class="text-2xl font-bold {{ $estadisticas['cobertura'] >= 100 ? 'text-green-600' : 'text-amber-600' }}">
                        {{ $estadisticas['cobertura'] }}%
                    </div>
                </div>
            </div>

            {{-- Alerta de secciones faltantes --}}
            @if(count($comparacion['sin_seccion']) > 0)
                <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm text-amber-700">
                                <strong>Hay {{ count($comparacion['sin_seccion']) }} módulos sin sección asignada.</strong>
                                Los usuarios de oficina no podrán acceder a estas rutas hasta que crees las secciones correspondientes.
                            </p>
                        </div>
                        <div class="ml-4">
                            <button @click="sincronizarTodas()"
                                    :disabled="sincronizando"
                                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                                <svg x-show="!sincronizando" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <svg x-show="sincronizando" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="sincronizando ? 'Creando...' : 'Crear Todas'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Secciones Existentes --}}
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Secciones Configuradas ({{ count($comparacion['con_seccion']) }})
                        </h3>
                        <p class="text-green-100 text-sm mt-1">Módulos con permisos gestionables</p>
                    </div>

                    <div class="p-4 max-h-[500px] overflow-y-auto">
                        @forelse($comparacion['con_seccion'] as $item)
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg border border-gray-100 mb-2">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ $item['seccion_nombre'] }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $item['prefijo'] }}.* ({{ $item['total_rutas'] }} rutas)</div>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    Activa
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                No hay secciones configuradas
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Secciones Faltantes --}}
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-red-500 to-rose-600 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Módulos Sin Sección ({{ count($comparacion['sin_seccion']) }})
                        </h3>
                        <p class="text-red-100 text-sm mt-1">Usuarios de oficina NO pueden acceder</p>
                    </div>

                    <div class="p-4 max-h-[500px] overflow-y-auto">
                        @forelse($comparacion['sin_seccion'] as $item)
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg border border-red-100 mb-2 bg-red-50/30">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ $item['nombre_sugerido'] }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $item['prefijo'] }}.* ({{ $item['total_rutas'] }} rutas)</div>
                                </div>
                                <button @click="crearSeccion('{{ $item['prefijo'] }}', '{{ $item['nombre_sugerido'] }}')"
                                        :disabled="creandoPrefijo === '{{ $item['prefijo'] }}'"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition disabled:opacity-50">
                                    <template x-if="creandoPrefijo === '{{ $item['prefijo'] }}'">
                                        <svg class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="creandoPrefijo !== '{{ $item['prefijo'] }}'">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </template>
                                    Crear
                                </button>
                            </div>
                        @empty
                            <div class="text-center py-8 text-green-600">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Todos los módulos tienen sección
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- Secciones Huérfanas (opcional) --}}
            @if(count($comparacion['secciones_huerfanas']) > 0)
                <div class="mt-6 bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Secciones Huérfanas ({{ count($comparacion['secciones_huerfanas']) }})
                        </h3>
                        <p class="text-gray-200 text-sm mt-1">No corresponden a ninguna ruta actual - considerar eliminar</p>
                    </div>

                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($comparacion['secciones_huerfanas'] as $item)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div>
                                        <div class="font-medium text-gray-700">{{ $item['nombre'] }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ $item['ruta'] }}</div>
                                    </div>
                                    <button @click="eliminarSeccion({{ $item['id'] }}, '{{ $item['nombre'] }}')"
                                            class="text-red-500 hover:text-red-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Información adicional --}}
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h4 class="font-semibold text-blue-800 mb-2">¿Cómo funciona?</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li><strong>Secciones</strong> = Agrupaciones de rutas por prefijo (ej: <code class="bg-blue-100 px-1 rounded">productos.*</code>)</li>
                    <li><strong>Departamentos</strong> = Grupos de usuarios con acceso a ciertas secciones</li>
                    <li><strong>Permisos</strong> = Ver / Crear / Editar por cada sección asignada al departamento</li>
                    <li class="pt-2"><strong>Flujo:</strong> Crear sección &rarr; Asignar a departamento &rarr; Asignar usuarios al departamento &rarr; Configurar permisos</li>
                </ul>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function seccionesManager() {
            return {
                sincronizando: false,
                creandoPrefijo: null,

                async sincronizarTodas() {
                    if (!confirm('¿Crear todas las secciones faltantes automáticamente?')) return;

                    this.sincronizando = true;
                    try {
                        const response = await fetch('{{ route("secciones.sincronizarTodas") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await response.json();

                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        alert('Error de conexión');
                        console.error(error);
                    } finally {
                        this.sincronizando = false;
                    }
                },

                async crearSeccion(prefijo, nombre) {
                    this.creandoPrefijo = prefijo;
                    try {
                        const response = await fetch('{{ route("secciones.crearParaPrefijo") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ prefijo, nombre })
                        });
                        const data = await response.json();

                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        alert('Error de conexión');
                        console.error(error);
                    } finally {
                        this.creandoPrefijo = null;
                    }
                },

                async eliminarSeccion(id, nombre) {
                    if (!confirm(`¿Eliminar la sección "${nombre}"? Los usuarios perderán acceso a esas rutas.`)) return;

                    try {
                        const response = await fetch(`/secciones/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await response.json();

                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        alert('Error de conexión');
                        console.error(error);
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
