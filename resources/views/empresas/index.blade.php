<x-app-layout>
    <x-slot name="title">Empresas - {{ config('app.name') }}</x-slot>
    <x-clave.modal-clave seccion="nominas" />
    <div class="py-6 px-4">

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <!-- Botones de navegación y título alineados a la izquierda -->
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex gap-3 flex-wrap">
                    <a href="{{ route('nominas.index') }}" wire:navigate
                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition">
                        ➕ Nóminas
                    </a>
                    <a href="{{ route('nomina.simulacion') }}" wire:navigate
                        class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow transition">
                        🧮 Simulación Nóminas
                    </a>
                </div>
            </div>
            <hr>
            <h1 class="text-2xl font-bold text-gray-800">
                📥 Importar Nóminas
            </h1>
            <!-- Formulario -->
            <form action="{{ route('nominas.dividir') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
                x-data="{ cargando: false }" @submit="cargando = true">
                @csrf
                <!-- Selección de mes -->

                <div class="max-w-xs">
                    <label for="mes_anio" class="block text-sm font-medium text-gray-700 mb-1">
                        Mes y año de las nóminas
                    </label>
                    <input type="month" name="mes_anio" id="mes_anio" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @error('mes_anio')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Archivo PDF -->
                <div class="max-w-xs">
                    <label for="archivo" class="block text-sm font-medium text-gray-700 mb-1">
                        Selecciona el PDF con las nóminas
                    </label>
                    <input type="file" name="archivo" id="archivo" accept=".pdf" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @error('archivo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Botón -->
                <div>
                    <x-boton-submit texto="Importar Nóminas" color="blue" :cargando="true" />
                </div>
            </form>
        </div>

        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Listado Empresas</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border">ID</th>
                        <th class="px-4 py-2 border">Nombre</th>
                        <th class="px-4 py-2 border">Dirección</th>
                        <th class="px-4 py-2 border">Localidad</th>
                        <th class="px-4 py-2 border">Provincia</th>
                        <th class="px-4 py-2 border">C.P.</th>
                        <th class="px-4 py-2 border">Teléfono</th>
                        <th class="px-4 py-2 border">Email</th>
                        <th class="px-4 py-2 border">NIF</th>
                        <th class="px-4 py-2 border">Nº S.S.</th>
                        <th class="px-4 py-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($empresas as $empresa)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $empresa->id }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->nombre }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->direccion }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->localidad }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->provincia }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->codigo_postal }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->telefono }}</td>
                            <td class="px-4 py-2 border">{{ $empresa->email }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->nif }}</td>
                            <td class="px-4 py-2 border text-center">{{ $empresa->numero_ss }}</td>
                            <td class="px-4 py-2 border text-center">
                                <a href="{{ route('empresas.show', $empresa->id) }}" wire:navigate
                                    class="text-blue-600 hover:underline">Ver</a>
                                <span class="mx-1">|</span>
                                <a href="{{ route('empresas.edit', $empresa->id) }}" wire:navigate
                                    class="text-green-600 hover:underline">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-gray-500">No hay empresas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Turnos Horarios</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border">ID</th>
                        <th class="px-4 py-2 border">Nombre</th>
                        <th class="px-4 py-2 border">hora_entrada</th>
                        <th class="px-4 py-2 border">hora_entrada</th>
                        <th class="px-4 py-2 border">hora_salida</th>
                        <th class="px-4 py-2 border">Salida_offset</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($turnos as $turno)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $turno->id }}</td>
                            <td class="px-4 py-2 border">{{ $turno->nombre }}</td>
                            <td class="px-4 py-2 border">{{ $turno->hora_entrada }}</td>
                            <td class="px-4 py-2 border">{{ $turno->entrada_offset }}</td>
                            <td class="px-4 py-2 border">{{ $turno->hora_salida }}</td>
                            <td class="px-4 py-2 border">{{ $turno->salida_offset }}</td>
                            <td class="px-4 py-2 border">
                                <a href="{{ route('turnos.show', $turno->id) }}" wire:navigate
                                    class="text-blue-600 hover:underline">Ver</a>
                                <span class="mx-1">|</span>
                                <a href="{{ route('turnos.edit', $turno->id) }}" wire:navigate
                                    class="text-green-600 hover:underline">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-gray-500">No hay empresas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Porcentajes Seguridad Social</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border">ID</th>
                        <th class="px-4 py-2 border">Concepto</th>
                        <th class="px-4 py-2 border">Porcentaje (%)</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($porcentajes_ss as $registro)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $registro->id }}</td>
                            <td class="px-4 py-2 border">{{ $registro->tipo_aportacion }}</td>
                            <td class="px-4 py-2 border text-center">
                                {{ number_format($registro->porcentaje, 2, ',', '.') }} %</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-500">No hay datos de porcentajes
                                disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>


        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Tramos IRPF</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border text-center">ID</th>
                        <th class="px-4 py-2 border text-center">Desde (€)</th>
                        <th class="px-4 py-2 border text-center">Hasta (€)</th>
                        <th class="px-4 py-2 border text-center">Porcentaje (%)</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($tramos as $tramo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $tramo->id }}</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($tramo->tramo_inicial, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 border text-right">
                                {{ $tramo->tramo_final !== null ? number_format($tramo->tramo_final, 2, ',', '.') : 'Sin límite' }}
                            </td>
                            <td class="px-4 py-2 border text-center">
                                {{ number_format($tramo->porcentaje, 2, ',', '.') }} %
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No hay tramos IRPF
                                registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Convenios por Categoría</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border text-center">ID</th>
                        <th class="px-4 py-2 border">Categoría</th>
                        <th class="px-4 py-2 border text-right">Salario Base</th>
                        <th class="px-4 py-2 border text-right">Liquido Minimo Pactado</th>
                        <th class="px-4 py-2 border text-right">Plus Asistencia</th>
                        <th class="px-4 py-2 border text-right">Plus Actividad</th>
                        <th class="px-4 py-2 border text-right">Plus Productividad</th>
                        <th class="px-4 py-2 border text-right">Plus Absentismo</th>
                        <th class="px-4 py-2 border text-right">Plus Transporte</th>
                        <th class="px-4 py-2 border text-right">Prorrateo Extras</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    @forelse ($convenio as $convenio)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-center">{{ $convenio->id }}</td>
                            <td class="px-4 py-2 border">{{ $convenio->categoria->nombre ?? 'Sin categoría' }}
                            </td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->salario_base, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->liquido_minimo_pactado, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_asistencia, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_actividad, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_productividad, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_absentismo, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->plus_transporte, 2, ',', '.') }} €</td>
                            <td class="px-4 py-2 border text-right">
                                {{ number_format($convenio->prorrateo_pagasextras, 2, ',', '.') }} €</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-gray-500">No hay convenios
                                registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Categorías</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto p-4"
            x-data="{
                categorias: @js($categorias),
                nuevaCategoria: '',
                enviando: false,
                editandoId: null,
                editandoNombre: '',
                originalNombre: '',

                iniciarEdicion(cat) {
                    this.editandoId = cat.id;
                    this.editandoNombre = cat.nombre;
                    this.originalNombre = cat.nombre;
                },

                cancelarEdicion() {
                    this.editandoId = null;
                    this.editandoNombre = '';
                    this.originalNombre = '';
                },

                guardar(cat) {
                    if (!this.editandoNombre.trim()) {
                        Swal.fire({ icon: 'warning', text: 'El nombre no puede estar vacío.' });
                        return;
                    }
                    const nuevoNombre = this.editandoNombre.trim();
                    guardarCategoria({ id: cat.id, nombre: nuevoNombre }, () => {
                        cat.nombre = nuevoNombre;
                        this.cancelarEdicion();
                    });
                },

                crear() {
                    if (!this.nuevaCategoria.trim() || this.enviando) return;
                    this.enviando = true;
                    crearCategoria(this.nuevaCategoria, (nuevaCat) => {
                        this.categorias.push(nuevaCat);
                        this.nuevaCategoria = '';
                        this.enviando = false;
                    });
                    // Reset en caso de error
                    setTimeout(() => { this.enviando = false; }, 3000);
                },

                eliminar(cat) {
                    eliminarCategoria(cat.id, cat.nombre, () => {
                        this.categorias = this.categorias.filter(c => c.id !== cat.id);
                    });
                }
            }">
            <!-- Formulario para añadir nueva categoría -->
            <div class="mb-4 flex gap-2">
                <input type="text" x-model="nuevaCategoria" placeholder="Nueva categoría..."
                    class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2"
                    @keydown.enter="crear()">
                <button type="button" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow transition"
                    @click="crear()" :disabled="enviando">
                    <span x-show="!enviando">Añadir</span>
                    <span x-show="enviando">...</span>
                </button>
            </div>

            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-4 py-2 border text-center w-20">ID</th>
                        <th class="px-4 py-2 border">Nombre</th>
                        <th class="px-4 py-2 border text-center w-40">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    <template x-for="cat in categorias" :key="cat.id">
                        <tr tabindex="0"
                            @dblclick="if(!$event.target.closest('input, button')) { editandoId === cat.id ? cancelarEdicion() : iniciarEdicion(cat); }"
                            @keydown.enter.stop="if(editandoId === cat.id) { guardar(cat); }"
                            @keydown.escape.stop="if(editandoId === cat.id) { cancelarEdicion(); }"
                            :class="{ 'bg-yellow-100': editandoId === cat.id, 'hover:bg-gray-50': editandoId !== cat.id }"
                            class="border-b cursor-pointer transition-colors">

                            <!-- ID -->
                            <td class="px-4 py-2 border text-center" x-text="cat.id"></td>

                            <!-- NOMBRE -->
                            <td class="px-4 py-2 border">
                                <span x-show="editandoId !== cat.id" x-text="cat.nombre"></span>
                                <input x-show="editandoId === cat.id" type="text" x-model="editandoNombre"
                                    class="w-full text-sm border rounded px-2 py-1 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    x-ref="inputEdicion">
                            </td>

                            <!-- ACCIONES -->
                            <td class="px-4 py-2 border text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Botones en modo edición -->
                                    <template x-if="editandoId === cat.id">
                                        <div class="flex items-center gap-2">
                                            <button @click="guardar(cat)"
                                                class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                                title="Guardar cambios">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button @click="cancelarEdicion()"
                                                class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                                title="Cancelar edición">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Botones en modo normal -->
                                    <template x-if="editandoId !== cat.id">
                                        <div class="flex items-center gap-2">
                                            <button @click="iniciarEdicion(cat)"
                                                class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                                title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="eliminar(cat)"
                                                class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                                title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="categorias.length === 0">
                        <td colspan="3" class="text-center py-4 text-gray-500">No hay categorías registradas.</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        window.guardarCategoria = function(categoria, callback) {
            if (!categoria.nombre || !categoria.nombre.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'El nombre no puede estar vacío.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            fetch("{{ route('categorias.updateField') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    id: categoria.id,
                    field: 'nombre',
                    value: categoria.nombre.trim()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar el original para que coincida con el editado
                    if (callback) callback();
                    // Toast de éxito
                    Swal.fire({
                        icon: 'success',
                        text: 'Categoría actualizada',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al actualizar',
                        text: data.message || 'Ha ocurrido un error inesperado.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo actualizar la categoría. Inténtalo nuevamente.',
                    confirmButtonText: 'OK'
                });
            });
        }

        window.crearCategoria = function(nombre, callback) {
            if (!nombre || !nombre.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'El nombre no puede estar vacío.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            fetch("{{ route('categorias.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ nombre: nombre.trim() })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (callback) callback(data.categoria);
                    Swal.fire({
                        icon: 'success',
                        text: 'Categoría creada',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al crear',
                        text: data.message || 'Ha ocurrido un error inesperado.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo crear la categoría. Inténtalo nuevamente.',
                    confirmButtonText: 'OK'
                });
            });
        }

        window.eliminarCategoria = function(id, nombre, callback) {
            Swal.fire({
                title: '¿Eliminar categoría?',
                text: `Se eliminará la categoría "${nombre}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("{{ route('categorias.destroy') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (callback) callback();
                            Swal.fire({
                                icon: 'success',
                                text: 'Categoría eliminada',
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo eliminar la categoría.',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        }
    </script>

</x-app-layout>
