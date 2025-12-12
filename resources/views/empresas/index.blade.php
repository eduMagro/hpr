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
        <div class="bg-white shadow-md rounded-lg overflow-x-auto"
            x-data="empresasData()"
            x-init="init()">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-2 py-2 border text-center w-16">ID</th>
                        <th class="px-2 py-2 border">Nombre</th>
                        <th class="px-2 py-2 border">Dirección</th>
                        <th class="px-2 py-2 border">Localidad</th>
                        <th class="px-2 py-2 border">Provincia</th>
                        <th class="px-2 py-2 border w-20">C.P.</th>
                        <th class="px-2 py-2 border">Teléfono</th>
                        <th class="px-2 py-2 border">Email</th>
                        <th class="px-2 py-2 border">NIF</th>
                        <th class="px-2 py-2 border">Nº S.S.</th>
                        <th class="px-2 py-2 border text-center w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    <template x-for="emp in empresas" :key="emp.id">
                        <tr :class="{ 'bg-yellow-100': editandoId === emp.id, 'hover:bg-gray-50': editandoId !== emp.id }"
                            @dblclick="if(!$event.target.closest('input, button')) { editandoId === emp.id ? cancelarEdicion() : iniciarEdicion(emp); }"
                            @keydown.enter.stop="if(editandoId === emp.id) { guardar(emp); }"
                            @keydown.escape.stop="if(editandoId === emp.id) { cancelarEdicion(); }"
                            tabindex="0" class="border-b cursor-pointer transition-colors">
                            <td class="px-2 py-1 border text-center" x-text="emp.id"></td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== emp.id" x-text="emp.nombre"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.nombre" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== emp.id" x-text="emp.direccion"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.direccion" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== emp.id" x-text="emp.localidad"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.localidad" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== emp.id" x-text="emp.provincia"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.provincia" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== emp.id" x-text="emp.codigo_postal"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.codigo_postal" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== emp.id" x-text="emp.telefono"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.telefono" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== emp.id" x-text="emp.email"></span>
                                <input x-show="editandoId === emp.id" type="email" x-model="editando.email" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== emp.id" x-text="emp.nif"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.nif" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== emp.id" x-text="emp.numero_ss"></span>
                                <input x-show="editandoId === emp.id" type="text" x-model="editando.numero_ss" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <template x-if="editandoId === emp.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="guardar(emp)" class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center" title="Guardar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button @click="cancelarEdicion()" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Cancelar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editandoId !== emp.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="iniciarEdicion(emp)" class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="eliminar(emp)" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="empresas.length === 0">
                        <td colspan="11" class="text-center py-4 text-gray-500">No hay empresas registradas.</td>
                    </tr>
                    <!-- Fila para añadir nueva empresa -->
                    <tr class="bg-green-50 border-t-2 border-green-300">
                        <td class="px-2 py-1 border text-center text-gray-400 text-xs">Nuevo</td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.nombre" placeholder="Nombre *" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.direccion" placeholder="Dirección" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.localidad" placeholder="Localidad" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.provincia" placeholder="Provincia" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.codigo_postal" placeholder="C.P." class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.telefono" placeholder="Teléfono" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="email" x-model="nueva.email" placeholder="Email" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.nif" placeholder="NIF" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border">
                            <input type="text" x-model="nueva.numero_ss" placeholder="Nº S.S." class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()">
                        </td>
                        <td class="px-2 py-1 border text-center">
                            <button @click="crear()" :disabled="enviando" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1 px-2 rounded shadow transition" title="Añadir empresa">
                                <span x-show="!enviando">+</span>
                                <span x-show="enviando">...</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Turnos Horarios</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto" x-data="turnosData()">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-2 py-2 border w-16">ID</th>
                        <th class="px-2 py-2 border">Nombre</th>
                        <th class="px-2 py-2 border">H. Entrada</th>
                        <th class="px-2 py-2 border">Offset Ent.</th>
                        <th class="px-2 py-2 border">H. Salida</th>
                        <th class="px-2 py-2 border">Offset Sal.</th>
                        <th class="px-2 py-2 border">Color</th>
                        <th class="px-2 py-2 border w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    <template x-for="item in items" :key="item.id">
                        <tr :class="{ 'bg-yellow-100': editandoId === item.id, 'hover:bg-gray-50': editandoId !== item.id }"
                            @dblclick="if(!$event.target.closest('input, button')) { editandoId === item.id ? cancelarEdicion() : iniciarEdicion(item); }"
                            @keydown.enter.stop="if(editandoId === item.id) { guardar(item); }"
                            @keydown.escape.stop="if(editandoId === item.id) { cancelarEdicion(); }"
                            tabindex="0" class="border-b cursor-pointer transition-colors">
                            <td class="px-2 py-1 border text-center" x-text="item.id"></td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== item.id" x-text="item.nombre"></span>
                                <input x-show="editandoId === item.id" type="text" x-model="editando.nombre" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== item.id" x-text="item.hora_entrada"></span>
                                <input x-show="editandoId === item.id" type="time" x-model="editando.hora_entrada" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== item.id" x-text="item.entrada_offset"></span>
                                <input x-show="editandoId === item.id" type="number" x-model="editando.entrada_offset" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== item.id" x-text="item.hora_salida"></span>
                                <input x-show="editandoId === item.id" type="time" x-model="editando.hora_salida" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== item.id" x-text="item.salida_offset"></span>
                                <input x-show="editandoId === item.id" type="number" x-model="editando.salida_offset" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== item.id" class="inline-block w-6 h-6 rounded" :style="'background-color:' + (item.color || '#ccc')"></span>
                                <input x-show="editandoId === item.id" type="color" x-model="editando.color" class="w-10 h-6 border rounded">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <template x-if="editandoId === item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="guardar(item)" class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center" title="Guardar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button @click="cancelarEdicion()" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Cancelar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editandoId !== item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="iniciarEdicion(item)" class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="eliminar(item)" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="8" class="text-center py-4 text-gray-500">No hay turnos registrados.</td>
                    </tr>
                    <!-- Fila nueva -->
                    <tr class="bg-green-50 border-t-2 border-green-300">
                        <td class="px-2 py-1 border text-center text-gray-400 text-xs">Nuevo</td>
                        <td class="px-2 py-1 border"><input type="text" x-model="nuevo.nombre" placeholder="Nombre *" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="time" x-model="nuevo.hora_entrada" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="number" x-model="nuevo.entrada_offset" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="time" x-model="nuevo.hora_salida" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="number" x-model="nuevo.salida_offset" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="color" x-model="nuevo.color" class="w-10 h-6 border rounded"></td>
                        <td class="px-2 py-1 border text-center">
                            <button @click="crear()" :disabled="enviando" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1 px-2 rounded shadow transition" title="Añadir">
                                <span x-show="!enviando">+</span><span x-show="enviando">...</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Porcentajes Seguridad Social</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto" x-data="porcentajesSSData()">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-2 py-2 border w-16">ID</th>
                        <th class="px-2 py-2 border">Concepto</th>
                        <th class="px-2 py-2 border w-32">Porcentaje (%)</th>
                        <th class="px-2 py-2 border w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    <template x-for="item in items" :key="item.id">
                        <tr :class="{ 'bg-yellow-100': editandoId === item.id, 'hover:bg-gray-50': editandoId !== item.id }"
                            @dblclick="if(!$event.target.closest('input, button')) { editandoId === item.id ? cancelarEdicion() : iniciarEdicion(item); }"
                            @keydown.enter.stop="if(editandoId === item.id) { guardar(item); }"
                            @keydown.escape.stop="if(editandoId === item.id) { cancelarEdicion(); }"
                            tabindex="0" class="border-b cursor-pointer transition-colors">
                            <td class="px-2 py-1 border text-center" x-text="item.id"></td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== item.id" x-text="item.tipo_aportacion"></span>
                                <input x-show="editandoId === item.id" type="text" x-model="editando.tipo_aportacion" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.porcentaje) + ' %'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.porcentaje" class="w-20 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <template x-if="editandoId === item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="guardar(item)" class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center" title="Guardar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button @click="cancelarEdicion()" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Cancelar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editandoId !== item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="iniciarEdicion(item)" class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="eliminar(item)" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="4" class="text-center py-4 text-gray-500">No hay datos de porcentajes disponibles.</td>
                    </tr>
                    <!-- Fila nueva -->
                    <tr class="bg-green-50 border-t-2 border-green-300">
                        <td class="px-2 py-1 border text-center text-gray-400 text-xs">Nuevo</td>
                        <td class="px-2 py-1 border"><input type="text" x-model="nuevo.tipo_aportacion" placeholder="Concepto *" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="number" step="0.01" x-model="nuevo.porcentaje" placeholder="0.00" class="w-20 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border text-center">
                            <button @click="crear()" :disabled="enviando" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1 px-2 rounded shadow transition" title="Añadir">
                                <span x-show="!enviando">+</span><span x-show="enviando">...</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Tramos IRPF</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto" x-data="tramosIrpfData()">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-2 py-2 border w-16">ID</th>
                        <th class="px-2 py-2 border">Desde (€)</th>
                        <th class="px-2 py-2 border">Hasta (€)</th>
                        <th class="px-2 py-2 border">Porcentaje (%)</th>
                        <th class="px-2 py-2 border w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    <template x-for="item in items" :key="item.id">
                        <tr :class="{ 'bg-yellow-100': editandoId === item.id, 'hover:bg-gray-50': editandoId !== item.id }"
                            @dblclick="if(!$event.target.closest('input, button')) { editandoId === item.id ? cancelarEdicion() : iniciarEdicion(item); }"
                            @keydown.enter.stop="if(editandoId === item.id) { guardar(item); }"
                            @keydown.escape.stop="if(editandoId === item.id) { cancelarEdicion(); }"
                            tabindex="0" class="border-b cursor-pointer transition-colors">
                            <td class="px-2 py-1 border text-center" x-text="item.id"></td>
                            <td class="px-2 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.tramo_inicial)"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.tramo_inicial" class="w-24 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="item.tramo_final !== null ? formatNumber(item.tramo_final) : 'Sin límite'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.tramo_final" placeholder="Sin límite" class="w-24 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.porcentaje) + ' %'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.porcentaje" class="w-20 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <template x-if="editandoId === item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="guardar(item)" class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center" title="Guardar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button @click="cancelarEdicion()" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Cancelar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editandoId !== item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="iniciarEdicion(item)" class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="eliminar(item)" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="5" class="text-center py-4 text-gray-500">No hay tramos IRPF registrados.</td>
                    </tr>
                    <!-- Fila nueva -->
                    <tr class="bg-green-50 border-t-2 border-green-300">
                        <td class="px-2 py-1 border text-center text-gray-400 text-xs">Nuevo</td>
                        <td class="px-2 py-1 border"><input type="number" step="0.01" x-model="nuevo.tramo_inicial" placeholder="Desde *" class="w-24 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="number" step="0.01" x-model="nuevo.tramo_final" placeholder="Hasta" class="w-24 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border"><input type="number" step="0.01" x-model="nuevo.porcentaje" placeholder="% *" class="w-20 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border text-center">
                            <button @click="crear()" :disabled="enviando" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1 px-2 rounded shadow transition" title="Añadir">
                                <span x-show="!enviando">+</span><span x-show="enviando">...</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Convenios por Categoría</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto" x-data="conveniosData()">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-2 py-2 border w-12">ID</th>
                        <th class="px-2 py-2 border">Categoría</th>
                        <th class="px-2 py-2 border">Sal. Base</th>
                        <th class="px-2 py-2 border">Liq. Min.</th>
                        <th class="px-2 py-2 border">P. Asist.</th>
                        <th class="px-2 py-2 border">P. Activ.</th>
                        <th class="px-2 py-2 border">P. Prod.</th>
                        <th class="px-2 py-2 border">P. Abs.</th>
                        <th class="px-2 py-2 border">P. Trans.</th>
                        <th class="px-2 py-2 border">Prorrateo</th>
                        <th class="px-2 py-2 border w-20">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    <template x-for="item in items" :key="item.id">
                        <tr :class="{ 'bg-yellow-100': editandoId === item.id, 'hover:bg-gray-50': editandoId !== item.id }"
                            @dblclick="if(!$event.target.closest('input, select, button')) { editandoId === item.id ? cancelarEdicion() : iniciarEdicion(item); }"
                            @keydown.enter.stop="if(editandoId === item.id) { guardar(item); }"
                            @keydown.escape.stop="if(editandoId === item.id) { cancelarEdicion(); }"
                            tabindex="0" class="border-b cursor-pointer transition-colors">
                            <td class="px-1 py-1 border text-center text-xs" x-text="item.id"></td>
                            <td class="px-1 py-1 border">
                                <span x-show="editandoId !== item.id" x-text="item.categoria?.nombre || 'Sin cat.'"></span>
                                <select x-show="editandoId === item.id" x-model="editando.categoria_id" class="w-full text-xs border rounded px-1 py-0.5">
                                    <template x-for="cat in categorias" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.nombre"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.salario_base) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.salario_base" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.liquido_minimo_pactado) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.liquido_minimo_pactado" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.plus_asistencia) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.plus_asistencia" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.plus_actividad) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.plus_actividad" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.plus_productividad) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.plus_productividad" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.plus_absentismo) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.plus_absentismo" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.plus_transporte) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.plus_transporte" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-right">
                                <span x-show="editandoId !== item.id" x-text="formatNumber(item.prorrateo_pagasextras) + ' €'"></span>
                                <input x-show="editandoId === item.id" type="number" step="0.01" x-model="editando.prorrateo_pagasextras" class="w-16 text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-1 py-1 border text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <template x-if="editandoId === item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="guardar(item)" class="w-5 h-5 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center" title="Guardar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button @click="cancelarEdicion()" class="w-5 h-5 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Cancelar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editandoId !== item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="iniciarEdicion(item)" class="w-5 h-5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="eliminar(item)" class="w-5 h-5 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="11" class="text-center py-4 text-gray-500">No hay convenios registrados.</td>
                    </tr>
                    <!-- Fila nueva -->
                    <tr class="bg-green-50 border-t-2 border-green-300">
                        <td class="px-1 py-1 border text-center text-gray-400 text-xs">Nuevo</td>
                        <td class="px-1 py-1 border">
                            <select x-model="nuevo.categoria_id" class="w-full text-xs border rounded px-1 py-0.5">
                                <option value="">Categoría *</option>
                                <template x-for="cat in categorias" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.nombre"></option>
                                </template>
                            </select>
                        </td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.salario_base" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.liquido_minimo_pactado" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.plus_asistencia" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.plus_actividad" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.plus_productividad" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.plus_absentismo" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.plus_transporte" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border"><input type="number" step="0.01" x-model="nuevo.prorrateo_pagasextras" placeholder="0" class="w-16 text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-1 py-1 border text-center">
                            <button @click="crear()" :disabled="enviando" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1 px-2 rounded shadow transition" title="Añadir">
                                <span x-show="!enviando">+</span><span x-show="enviando">...</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 class="text-lg font-semibold px-4 pt-4 text-gray-800">Categorías</h3>
        <div class="bg-white shadow-md rounded-lg overflow-x-auto" x-data="categoriasData()">
            <table class="min-w-full table-auto border border-gray-300">
                <thead class="bg-gray-100 text-gray-700 text-sm uppercase">
                    <tr>
                        <th class="px-2 py-2 border w-16">ID</th>
                        <th class="px-2 py-2 border">Nombre</th>
                        <th class="px-2 py-2 border w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                    <template x-for="item in items" :key="item.id">
                        <tr :class="{ 'bg-yellow-100': editandoId === item.id, 'hover:bg-gray-50': editandoId !== item.id }"
                            @dblclick="if(!$event.target.closest('input, button')) { editandoId === item.id ? cancelarEdicion() : iniciarEdicion(item); }"
                            @keydown.enter.stop="if(editandoId === item.id) { guardar(item); }"
                            @keydown.escape.stop="if(editandoId === item.id) { cancelarEdicion(); }"
                            tabindex="0" class="border-b cursor-pointer transition-colors">
                            <td class="px-2 py-1 border text-center" x-text="item.id"></td>
                            <td class="px-2 py-1 border">
                                <span x-show="editandoId !== item.id" x-text="item.nombre"></span>
                                <input x-show="editandoId === item.id" type="text" x-model="editando.nombre" class="w-full text-xs border rounded px-1 py-0.5">
                            </td>
                            <td class="px-2 py-1 border text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <template x-if="editandoId === item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="guardar(item)" class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center" title="Guardar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button @click="cancelarEdicion()" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Cancelar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editandoId !== item.id">
                                        <div class="flex items-center gap-1">
                                            <button @click="iniciarEdicion(item)" class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="eliminar(item)" class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="3" class="text-center py-4 text-gray-500">No hay categorías registradas.</td>
                    </tr>
                    <!-- Fila nueva -->
                    <tr class="bg-green-50 border-t-2 border-green-300">
                        <td class="px-2 py-1 border text-center text-gray-400 text-xs">Nuevo</td>
                        <td class="px-2 py-1 border"><input type="text" x-model="nuevo.nombre" placeholder="Nombre categoría *" class="w-full text-xs border rounded px-1 py-0.5" @keydown.enter="crear()"></td>
                        <td class="px-2 py-1 border text-center">
                            <button @click="crear()" :disabled="enviando" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1 px-2 rounded shadow transition" title="Añadir">
                                <span x-show="!enviando">+</span><span x-show="enviando">...</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        // Función helper para formatear números
        function formatNumber(value) {
            if (value === null || value === undefined) return '0,00';
            return parseFloat(value).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // === TURNOS CRUD ===
        function turnosData() {
            return {
                items: @js($turnos),
                editandoId: null,
                editando: {},
                nuevo: { nombre: '', hora_entrada: '', entrada_offset: 0, hora_salida: '', salida_offset: 0, color: '#3b82f6' },
                enviando: false,

                iniciarEdicion(item) {
                    this.editandoId = item.id;
                    this.editando = { ...item };
                },
                cancelarEdicion() {
                    this.editandoId = null;
                    this.editando = {};
                },
                guardar(item) {
                    if (!this.editando.nombre?.trim()) {
                        Swal.fire({ icon: 'warning', text: 'El nombre es obligatorio.' });
                        return;
                    }
                    const campos = ['nombre', 'hora_entrada', 'entrada_offset', 'hora_salida', 'salida_offset', 'color'];
                    const promesas = campos.filter(c => this.editando[c] !== item[c]).map(field => {
                        return fetch("{{ route('turnos.updateField') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ id: item.id, field, value: this.editando[field] || '' })
                        });
                    });
                    Promise.all(promesas).then(() => {
                        campos.forEach(c => item[c] = this.editando[c]);
                        this.cancelarEdicion();
                        Swal.fire({ icon: 'success', text: 'Turno actualizado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                    }).catch(() => Swal.fire({ icon: 'error', text: 'Error al actualizar' }));
                },
                crear() {
                    if (!this.nuevo.nombre?.trim() || this.enviando) return;
                    this.enviando = true;
                    fetch("{{ route('turnos.storeJson') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(this.nuevo)
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            this.items.push(data.turno);
                            this.nuevo = { nombre: '', hora_entrada: '', entrada_offset: 0, hora_salida: '', salida_offset: 0, color: '#3b82f6' };
                            Swal.fire({ icon: 'success', text: 'Turno creado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                        } else Swal.fire({ icon: 'error', text: data.message });
                        this.enviando = false;
                    }).catch(() => { Swal.fire({ icon: 'error', text: 'Error al crear' }); this.enviando = false; });
                },
                eliminar(item) {
                    Swal.fire({ title: '¿Eliminar turno?', text: `Se eliminará "${item.nombre}"`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then(result => {
                        if (result.isConfirmed) {
                            fetch("{{ route('turnos.destroyJson') }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ id: item.id })
                            }).then(r => r.json()).then(data => {
                                if (data.success) {
                                    this.items = this.items.filter(i => i.id !== item.id);
                                    Swal.fire({ icon: 'success', text: 'Turno eliminado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                } else Swal.fire({ icon: 'error', text: data.message });
                            });
                        }
                    });
                }
            };
        }

        // === PORCENTAJES SS CRUD ===
        function porcentajesSSData() {
            return {
                items: @js($porcentajes_ss),
                editandoId: null,
                editando: {},
                nuevo: { tipo_aportacion: '', porcentaje: 0 },
                enviando: false,

                formatNumber,
                iniciarEdicion(item) { this.editandoId = item.id; this.editando = { ...item }; },
                cancelarEdicion() { this.editandoId = null; this.editando = {}; },
                guardar(item) {
                    if (!this.editando.tipo_aportacion?.trim()) { Swal.fire({ icon: 'warning', text: 'El concepto es obligatorio.' }); return; }
                    const campos = ['tipo_aportacion', 'porcentaje'];
                    const promesas = campos.filter(c => this.editando[c] !== item[c]).map(field => {
                        return fetch("{{ route('porcentajesSS.updateField') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ id: item.id, field, value: this.editando[field] || '' })
                        });
                    });
                    Promise.all(promesas).then(() => {
                        campos.forEach(c => item[c] = this.editando[c]);
                        this.cancelarEdicion();
                        Swal.fire({ icon: 'success', text: 'Porcentaje actualizado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                    }).catch(() => Swal.fire({ icon: 'error', text: 'Error al actualizar' }));
                },
                crear() {
                    if (!this.nuevo.tipo_aportacion?.trim() || this.enviando) return;
                    this.enviando = true;
                    fetch("{{ route('porcentajesSS.storeJson') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(this.nuevo)
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            this.items.push(data.porcentaje);
                            this.nuevo = { tipo_aportacion: '', porcentaje: 0 };
                            Swal.fire({ icon: 'success', text: 'Porcentaje creado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                        } else Swal.fire({ icon: 'error', text: data.message });
                        this.enviando = false;
                    }).catch(() => { Swal.fire({ icon: 'error', text: 'Error al crear' }); this.enviando = false; });
                },
                eliminar(item) {
                    Swal.fire({ title: '¿Eliminar porcentaje?', text: `Se eliminará "${item.tipo_aportacion}"`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then(result => {
                        if (result.isConfirmed) {
                            fetch("{{ route('porcentajesSS.destroyJson') }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ id: item.id })
                            }).then(r => r.json()).then(data => {
                                if (data.success) {
                                    this.items = this.items.filter(i => i.id !== item.id);
                                    Swal.fire({ icon: 'success', text: 'Porcentaje eliminado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                } else Swal.fire({ icon: 'error', text: data.message });
                            });
                        }
                    });
                }
            };
        }

        // === TRAMOS IRPF CRUD ===
        function tramosIrpfData() {
            return {
                items: @js($tramos),
                editandoId: null,
                editando: {},
                nuevo: { tramo_inicial: '', tramo_final: '', porcentaje: '' },
                enviando: false,

                formatNumber,
                iniciarEdicion(item) { this.editandoId = item.id; this.editando = { ...item }; },
                cancelarEdicion() { this.editandoId = null; this.editando = {}; },
                guardar(item) {
                    const campos = ['tramo_inicial', 'tramo_final', 'porcentaje'];
                    const promesas = campos.filter(c => this.editando[c] !== item[c]).map(field => {
                        return fetch("{{ route('tramosIrpf.updateField') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ id: item.id, field, value: this.editando[field] || '' })
                        });
                    });
                    Promise.all(promesas).then(() => {
                        campos.forEach(c => item[c] = this.editando[c]);
                        this.cancelarEdicion();
                        Swal.fire({ icon: 'success', text: 'Tramo actualizado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                    }).catch(() => Swal.fire({ icon: 'error', text: 'Error al actualizar' }));
                },
                crear() {
                    if (!this.nuevo.tramo_inicial || !this.nuevo.porcentaje || this.enviando) return;
                    this.enviando = true;
                    fetch("{{ route('tramosIrpf.storeJson') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(this.nuevo)
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            this.items.push(data.tramo);
                            this.nuevo = { tramo_inicial: '', tramo_final: '', porcentaje: '' };
                            Swal.fire({ icon: 'success', text: 'Tramo creado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                        } else Swal.fire({ icon: 'error', text: data.message });
                        this.enviando = false;
                    }).catch(() => { Swal.fire({ icon: 'error', text: 'Error al crear' }); this.enviando = false; });
                },
                eliminar(item) {
                    Swal.fire({ title: '¿Eliminar tramo?', text: `Se eliminará el tramo ${item.tramo_inicial} - ${item.tramo_final || 'Sin límite'}`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then(result => {
                        if (result.isConfirmed) {
                            fetch("{{ route('tramosIrpf.destroyJson') }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ id: item.id })
                            }).then(r => r.json()).then(data => {
                                if (data.success) {
                                    this.items = this.items.filter(i => i.id !== item.id);
                                    Swal.fire({ icon: 'success', text: 'Tramo eliminado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                } else Swal.fire({ icon: 'error', text: data.message });
                            });
                        }
                    });
                }
            };
        }

        // === CONVENIOS CRUD ===
        function conveniosData() {
            return {
                items: @js($convenio->load('categoria')),
                categorias: @js($categorias),
                editandoId: null,
                editando: {},
                nuevo: { categoria_id: '', salario_base: 0, liquido_minimo_pactado: 0, plus_asistencia: 0, plus_actividad: 0, plus_productividad: 0, plus_absentismo: 0, plus_transporte: 0, prorrateo_pagasextras: 0 },
                enviando: false,

                formatNumber,
                iniciarEdicion(item) { this.editandoId = item.id; this.editando = { ...item }; },
                cancelarEdicion() { this.editandoId = null; this.editando = {}; },
                guardar(item) {
                    const campos = ['categoria_id', 'salario_base', 'liquido_minimo_pactado', 'plus_asistencia', 'plus_actividad', 'plus_productividad', 'plus_absentismo', 'plus_transporte', 'prorrateo_pagasextras'];
                    const promesas = campos.filter(c => this.editando[c] !== item[c]).map(field => {
                        return fetch("{{ route('convenios.updateField') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ id: item.id, field, value: this.editando[field] || '' })
                        });
                    });
                    Promise.all(promesas).then(() => {
                        campos.forEach(c => item[c] = this.editando[c]);
                        if (this.editando.categoria_id !== item.categoria_id) {
                            item.categoria = this.categorias.find(c => c.id == this.editando.categoria_id);
                        }
                        this.cancelarEdicion();
                        Swal.fire({ icon: 'success', text: 'Convenio actualizado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                    }).catch(() => Swal.fire({ icon: 'error', text: 'Error al actualizar' }));
                },
                crear() {
                    if (!this.nuevo.categoria_id || this.enviando) { Swal.fire({ icon: 'warning', text: 'Selecciona una categoría' }); return; }
                    this.enviando = true;
                    fetch("{{ route('convenios.storeJson') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(this.nuevo)
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            this.items.push(data.convenio);
                            this.nuevo = { categoria_id: '', salario_base: 0, liquido_minimo_pactado: 0, plus_asistencia: 0, plus_actividad: 0, plus_productividad: 0, plus_absentismo: 0, plus_transporte: 0, prorrateo_pagasextras: 0 };
                            Swal.fire({ icon: 'success', text: 'Convenio creado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                        } else Swal.fire({ icon: 'error', text: data.message });
                        this.enviando = false;
                    }).catch(() => { Swal.fire({ icon: 'error', text: 'Error al crear' }); this.enviando = false; });
                },
                eliminar(item) {
                    Swal.fire({ title: '¿Eliminar convenio?', text: `Se eliminará el convenio de "${item.categoria?.nombre || 'Sin categoría'}"`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then(result => {
                        if (result.isConfirmed) {
                            fetch("{{ route('convenios.destroyJson') }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ id: item.id })
                            }).then(r => r.json()).then(data => {
                                if (data.success) {
                                    this.items = this.items.filter(i => i.id !== item.id);
                                    Swal.fire({ icon: 'success', text: 'Convenio eliminado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                } else Swal.fire({ icon: 'error', text: data.message });
                            });
                        }
                    });
                }
            };
        }

        // === EMPRESAS CRUD ===
        function empresasData() {
            return {
                empresas: @js($empresas),
                editandoId: null,
                editando: {},
                nueva: { nombre: '', direccion: '', localidad: '', provincia: '', codigo_postal: '', telefono: '', email: '', nif: '', numero_ss: '' },
                enviando: false,

                init() {},

                iniciarEdicion(emp) {
                    this.editandoId = emp.id;
                    this.editando = { ...emp };
                },

                cancelarEdicion() {
                    this.editandoId = null;
                    this.editando = {};
                },

                guardar(emp) {
                    if (!this.editando.nombre || !this.editando.nombre.trim()) {
                        Swal.fire({ icon: 'warning', text: 'El nombre es obligatorio.' });
                        return;
                    }
                    const campos = ['nombre', 'direccion', 'localidad', 'provincia', 'codigo_postal', 'telefono', 'email', 'nif', 'numero_ss'];
                    const promesas = campos.filter(c => this.editando[c] !== emp[c]).map(field => {
                        return fetch("{{ route('empresas.updateField') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ id: emp.id, field: field, value: this.editando[field] || '' })
                        });
                    });

                    Promise.all(promesas).then(() => {
                        campos.forEach(c => emp[c] = this.editando[c]);
                        this.cancelarEdicion();
                        Swal.fire({ icon: 'success', text: 'Empresa actualizada', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                    }).catch(() => {
                        Swal.fire({ icon: 'error', text: 'Error al actualizar' });
                    });
                },

                crear() {
                    if (!this.nueva.nombre || !this.nueva.nombre.trim() || this.enviando) return;
                    this.enviando = true;
                    fetch("{{ route('empresas.storeJson') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.nueva)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.empresas.push(data.empresa);
                            this.nueva = { nombre: '', direccion: '', localidad: '', provincia: '', codigo_postal: '', telefono: '', email: '', nif: '', numero_ss: '' };
                            Swal.fire({ icon: 'success', text: 'Empresa creada', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', text: data.message });
                        }
                        this.enviando = false;
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', text: 'Error al crear' });
                        this.enviando = false;
                    });
                },

                eliminar(emp) {
                    Swal.fire({
                        title: '¿Eliminar empresa?',
                        text: `Se eliminará "${emp.nombre}"`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('empresas.destroyJson') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ id: emp.id })
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    this.empresas = this.empresas.filter(e => e.id !== emp.id);
                                    Swal.fire({ icon: 'success', text: 'Empresa eliminada', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                } else {
                                    Swal.fire({ icon: 'error', text: data.message });
                                }
                            });
                        }
                    });
                }
            };
        }

        // === CATEGORIAS CRUD ===
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
