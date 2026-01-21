<x-app-layout>
    <style>
        /* Hide number input spinners */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
    <div x-data="gastosManager()" class="py-12 dark:bg-gray-900 min-h-screen">
        <div class="max-w-[95%] mx-auto sm:px-6 lg:px-8">

            <!-- Success Message -->
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                    class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Gestión de Gastos
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Visualiza y gestiona los gastos de la empresa de forma eficiente.
                    </p>
                </div>

                <!-- Controls -->
                <div class="flex items-center gap-4">
                    <button @click="openCreateModal()"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Gasto
                    </button>

                    <div
                        class="flex items-center gap-4 bg-white dark:bg-gray-800 p-2 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <form method="GET" action="{{ route('gastos.index') }}" class="flex items-center gap-3 px-2">
                            <label for="per_page" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                Mostrar:
                            </label>
                            <div class="relative">
                                <select name="per_page" id="per_page" onchange="this.form.submit()"
                                    class="appearance-none bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 py-1.5 pl-3 pr-8 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer transition-shadow hover:shadow-md">
                                    @foreach ([10, 25, 100, 500] as $amount)
                                        <option value="{{ $amount }}" {{ $perPage == $amount ? 'selected' : '' }}>
                                            {{ $amount }}
                                        </option>
                                    @endforeach
                                </select>
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden backdrop-blur-sm">
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead
                            class="text-xs text-gray-700 uppercase bg-gray-50/50 dark:bg-gray-700/50 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-bold tracking-wider rounded-tl-2xl">ID</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Fecha Pedido</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Fecha Llegada</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Nave</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Obra</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Proveedor</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Máquina</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Motivo</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-right">Coste</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">Factura</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Observaciones</th>
                                <th scope="col" class="px-6 py-4 font-semibold rounded-tr-2xl text-right">Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @forelse($gastos as $gasto)
                                <tr
                                    class="bg-white dark:bg-gray-800 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200 group">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                        #{{ $gasto->id }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $gasto->fecha_pedido ? \Carbon\Carbon::parse($gasto->fecha_pedido)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $gasto->fecha_llegada ? \Carbon\Carbon::parse($gasto->fecha_llegada)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($gasto->nave)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                {{ $gasto->nave->obra ?? 'Nave ' . $gasto->nave_id }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($gasto->obra)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                                {{ $gasto->obra->obra ?? 'Obra ' . $gasto->obra_id }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-200">
                                        {{ $gasto->proveedor ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($gasto->maquina)
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                                <span>{{ $gasto->maquina->nombre ?? 'Maq. ' . $gasto->maquina_id }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $gasto->motivo ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                        @if ($gasto->coste)
                                            {{ number_format($gasto->coste, 2, ',', '.') }} €
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($gasto->factura)
                                            <a href="#"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
                                                <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                    </path>
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate">
                                        {{ $gasto->observaciones ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="editGasto({{ $gasto }})"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12"
                                        class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 mb-4 text-gray-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <p class="text-lg font-medium">No hay gastos registrados</p>
                                            <p class="text-sm">Empieza añadiendo nuevos registros al sistema.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Mostrando <span class="font-medium">{{ $gastos->firstItem() ?? 0 }}</span> a <span
                            class="font-medium">{{ $gastos->lastItem() ?? 0 }}</span> de <span
                            class="font-medium">{{ $gastos->total() }}</span> resultados
                    </div>
                    <div>
                        {{ $gastos->appends(['per_page' => $perPage])->links() }}
                    </div>
                </div>
            </div>

            <!-- Graphs Section (Placeholders) -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Global Stats -->
                <div
                    class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-24 h-24 text-indigo-500" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                        Resumen Global
                    </h3>
                    <div class="mt-6 flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold text-gray-900 dark:text-white">
                            {{ number_format($stats['global'], 2, ',', '.') }} €
                        </span>
                        <span class="text-sm text-gray-500">Total Gastos</span>
                    </div>

                    <!-- Graph Placeholder Box -->
                    <div
                        class="mt-6 h-48 bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-gray-700 dark:to-gray-800 rounded-2xl border border-dashed border-indigo-200 dark:border-gray-600 flex items-center justify-center">
                        <span class="text-indigo-400 dark:text-gray-400 font-medium animate-pulse">Gráfica Global
                            (Próximamente)</span>
                    </div>
                </div>

                <!-- Monthly Stats -->
                <div
                    class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700 relative overflow-hidden group hover:shadow-xl transition-all duration-300">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg class="w-24 h-24 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
                        Resumen Mensual
                    </h3>
                    <div class="mt-6 flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold text-gray-900 dark:text-white">
                            {{ number_format($stats['mensual'], 2, ',', '.') }} €
                        </span>
                        <span class="text-sm text-gray-500">Este Mes</span>
                    </div>

                    <!-- Graph Placeholder Box -->
                    <div
                        class="mt-6 h-48 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-gray-700 dark:to-gray-800 rounded-2xl border border-dashed border-emerald-200 dark:border-gray-600 flex items-center justify-center">
                        <span class="text-emerald-400 dark:text-gray-400 font-medium animate-pulse">Gráfica Mensual
                            (Próximamente)</span>
                    </div>
                </div>
            </div>

            <!-- MODAL -->
            <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
                aria-labelledby="modal-title" role="dialog" aria-modal="true">

                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                        @click="closeModal()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div
                        class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                        <div
                            class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-2xl font-bold leading-6 text-gray-900 dark:text-white" id="modal-title">
                                <span x-text="isEditing ? 'Editar Gasto' : 'Nuevo Gasto'"></span>
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Rellena la información del gasto.
                            </p>
                        </div>

                        <form :action="formAction" method="POST" class="p-6">
                            @csrf
                            <input type="hidden" name="_method" :value="isEditing ? 'PUT' : 'POST'">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <!-- Fecha Pedido -->
                                <div>
                                    <label for="fecha_pedido"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha
                                        Pedido</label>
                                    <input type="date" name="fecha_pedido" id="fecha_pedido"
                                        x-model="form.fecha_pedido"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                </div>

                                <!-- Fecha Llegada -->
                                <div>
                                    <label for="fecha_llegada"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha
                                        Llegada</label>
                                    <input type="date" name="fecha_llegada" id="fecha_llegada"
                                        x-model="form.fecha_llegada"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                </div>

                                <!-- Nave -->
                                <div>
                                    <label for="nave_id"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nave</label>
                                    <select name="nave_id" id="nave_id" x-model="form.nave_id"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                        <option value="">Seleccionar Nave</option>
                                        @foreach ($obras as $obra)
                                            <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Obra -->
                                <div>
                                    <label for="obra_id"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Obra</label>
                                    <select name="obra_id" id="obra_id" x-model="form.obra_id"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                        <option value="">Seleccionar Obra</option>
                                        @foreach ($obras as $obra)
                                            <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Proveedor -->
                                <div>
                                    <label for="proveedor"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proveedor</label>
                                    <input type="text" name="proveedor" id="proveedor" x-model="form.proveedor"
                                        list="proveedores_list"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                        placeholder="Escribe o selecciona...">
                                    <datalist id="proveedores_list">
                                        @foreach ($proveedoresLista as $prov)
                                            <option value="{{ $prov }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>

                                <!-- Maquina -->
                                <div>
                                    <label for="maquina_id"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máquina</label>
                                    <select name="maquina_id" id="maquina_id" x-model="form.maquina_id"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                        <option value="">Seleccionar Máquina</option>
                                        @foreach ($maquinas as $maq)
                                            <option value="{{ $maq->id }}">{{ $maq->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Motivo -->
                                <div class="col-span-2">
                                    <label for="motivo"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo</label>
                                    <input type="text" name="motivo" id="motivo" x-model="form.motivo"
                                        list="motivos_list"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                        placeholder="Escribe o selecciona...">
                                    <datalist id="motivos_list">
                                        @foreach ($motivosLista as $mot)
                                            <option value="{{ $mot }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>

                                <!-- Coste -->
                                <div>
                                    <label for="coste"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Coste
                                        (€)</label>
                                    <input type="number" step="0.01" name="coste" id="coste"
                                        x-model="form.coste"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                </div>

                                <!-- Observaciones -->
                                <div class="col-span-2">
                                    <label for="observaciones"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                    <textarea name="observaciones" id="observaciones" rows="3" x-model="form.observaciones"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                                </div>

                            </div>

                            <div class="mt-8 flex justify-end gap-3">
                                <button type="button" @click="closeModal()"
                                    class="inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-10 items-center sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-10 items-center sm:text-sm transition-transform active:scale-95">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function gastosManager() {
            return {
                showModal: false,
                isEditing: false,
                form: {
                    fecha_pedido: '',
                    fecha_llegada: '',
                    nave_id: '',
                    obra_id: '',
                    proveedor: '',
                    maquina_id: '',
                    motivo: '',
                    coste: '',
                    observaciones: ''
                },
                formAction: '{{ route('gastos.store') }}',

                openCreateModal() {
                    this.resetForm();
                    this.isEditing = false;
                    this.formAction = '{{ route('gastos.store') }}';
                    this.showModal = true;
                },

                editGasto(gasto) {
                    this.form = {
                        fecha_pedido: gasto.fecha_pedido,
                        fecha_llegada: gasto.fecha_llegada,
                        nave_id: gasto.nave_id,
                        obra_id: gasto.obra_id,
                        proveedor: gasto.proveedor,
                        maquina_id: gasto.maquina_id,
                        motivo: gasto.motivo,
                        coste: gasto.coste,
                        observaciones: gasto.observaciones
                    };
                    this.isEditing = true;
                    // Dynamically construct route: gastos.update usually requires /gastos/{id}
                    this.formAction = `/gastos/${gasto.id}`;
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                },

                resetForm() {
                    this.form = {
                        fecha_pedido: new Date().toISOString().split('T')[0], // Default to today
                        fecha_llegada: '',
                        nave_id: '',
                        obra_id: '',
                        proveedor: '',
                        maquina_id: '',
                        motivo: '',
                        coste: '',
                        observaciones: ''
                    };
                }
            }
        }
    </script>
</x-app-layout>
