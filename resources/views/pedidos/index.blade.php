<x-app-layout>
    <x-slot name="title">Pedidos - {{ config('app.name') }}</x-slot>

    {{-- Estilos personalizados para el carrito --}}
    <style>
        /* Ocultar spinners del input number */
        .cart-input::-webkit-outer-spin-button,
        .cart-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cart-input[type=number] {
            -moz-appearance: textfield;
        }

        /* Scrollbar personalizada para el carrito */
        .cart-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .cart-scrollbar::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 10px;
        }

        .cart-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(71, 85, 105, 0.8);
            border-radius: 10px;
        }

        .cart-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 1);
        }

        /* Firefox */
        .cart-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(71, 85, 105, 0.8) rgba(30, 41, 59, 0.5);
        }
    </style>

    <div class="px-4 py-6" x-data="{
        activeTab: 'activos',
        cart: [],
        toggleItem(item) {
            const idx = this.cart.findIndex(i => i.id === item.id);
            if (idx > -1) {
                this.cart.splice(idx, 1);
            } else {
                // Convertir cantidad a número al añadir
                this.cart.push({
                    ...item,
                    cantidad: parseFloat(item.cantidad) || 0
                });
            }
        },
        isInCart(id) {
            return this.cart.some(i => i.id === id);
        },
        get cartTotal() {
            return this.cart.reduce((sum, item) => sum + parseFloat(item.cantidad || 0), 0);
        }
    }">
        @if (auth()->user()->rol === 'oficina')
            {{-- Navegación de Pestañas Premium --}}
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div
                    class="inline-flex p-1.5 bg-gray-100/80 backdrop-blur-md rounded-2xl border border-gray-200 shadow-inner">
                    <button @click="activeTab = 'activos'"
                        :class="activeTab === 'activos' ? 'bg-white text-blue-700 shadow-md ring-1 ring-black/5' :
                            'text-gray-500 hover:text-gray-700 hover:bg-white/50'"
                        class="px-8 py-3 rounded-xl text-sm font-bold transition-all duration-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                        Pedidos Activos
                    </button>
                    <button @click="activeTab = 'analisis'"
                        :class="activeTab === 'analisis' ? 'bg-white text-blue-700 shadow-md ring-1 ring-black/5' :
                            'text-gray-500 hover:text-gray-700 hover:bg-white/50'"
                        class="px-8 py-3 rounded-xl text-sm font-bold transition-all duration-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Análisis y Reposición
                    </button>
                </div>

                <div x-show="activeTab === 'analisis' && cart.length > 0"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <button @click="mostrarConfirmacion()"
                        class="bg-gradient-to-r from-green-600 to-emerald-700 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-xl hover:shadow-green-500/20 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        Generar Pedido (<span x-text="cart.length"></span>)
                    </button>
                </div>
            </div>

            <div x-show="activeTab === 'activos'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                @livewire('pedidos-table')
            </div>

            <div x-show="activeTab === 'analisis'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                {{-- LAYOUT GRID: Contenido + Carrito --}}
                <div class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-8 items-start">
                    {{-- COLUMNA IZQUIERDA: Contenido Principal --}}
                    <div class="space-y-8 min-w-0">
                        {{-- SUMMARY CARDS --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div
                                class="bg-gradient-to-br from-blue-600 to-indigo-700 p-6 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden group">
                                <div class="relative z-10">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="p-2 bg-white/20 backdrop-blur-md rounded-xl">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                                </path>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-black uppercase tracking-widest text-blue-100">Stock
                                            Total Empresa</span>
                                    </div>
                                    <h4 class="text-3xl font-black mb-1">
                                        {{ number_format($totalStockEmpresa, 0, ',', '.') }} <span
                                            class="text-lg font-medium opacity-80">kg</span>
                                    </h4>
                                    <p class="text-[10px] text-blue-100/70 font-bold uppercase">Consolidado todas las
                                        naves</p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="absolute -bottom-8 -right-8 w-40 h-40 text-white opacity-10 transform rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500 pointer-events-none lucide lucide-anvil-icon lucide-anvil">
                                    <path d="M7 10H6a4 4 0 0 1-4-4 1 1 0 0 1 1-1h4" />
                                    <path d="M7 5a1 1 0 0 1 1-1h13a1 1 0 0 1 1 1 7 7 0 0 1-7 7H8a1 1 0 0 1-1-1z" />
                                    <path d="M9 12v5" />
                                    <path d="M15 12v5" />
                                    <path d="M5 20a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3 1 1 0 0 1-1 1H6a1 1 0 0 1-1-1" />
                                </svg>
                            </div>

                            <div class="md:col-span-2 bg-white p-6 rounded-[1.5rem] shadow-sm border border-gray-100">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-gray-50 rounded-xl">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <span
                                        class="text-xs font-black uppercase tracking-widest text-gray-400">Distribución
                                        por Naves</span>
                                </div>
                                <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
                                    @foreach ($stockPorNaves as $resumen)
                                        <div
                                            class="flex-shrink-0 bg-gray-50/50 border border-gray-100 p-4 rounded-2xl min-w-[160px] hover:border-blue-200 transition-colors">
                                            <p
                                                class="text-[10px] font-bold text-gray-400 mb-1 uppercase tracking-tight">
                                                {{ $resumen['nombre'] }}</p>
                                            <p class="text-base font-black text-gray-800">
                                                {{ number_format($resumen['total'], 0, ',', '.') }} <span
                                                    class="text-[10px] font-bold opacity-40">kg</span></p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN DE STOCK --}}
                        <div class="p-6 rounded-3xl flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-blue-50 rounded-2xl ring-1 ring-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-blue-600">
                                        <path
                                            d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-gray-900 tracking-tight italic uppercase">
                                        Análisis detallado</h3>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Filtrar
                                        tabla de reposición por nave</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <select name="obra_id_hpr" id="obra_id_hpr_stock"
                                    class="min-w-[240px] rounded-xl border-gray-200 text-sm font-medium shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200">
                                    <option value="">-- Todas las naves --</option>
                                    @foreach ($obrasHpr as $obra)
                                        <option value="{{ $obra->id }}"
                                            {{ request('obra_id_hpr') == $obra->id ? 'selected' : '' }}>
                                            {{ $obra->obra }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="loading-stock" class="hidden">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div id="contenedor-stock" class="transition-all duration-300">
                            <x-estadisticas.stock :nombre-meses="$nombreMeses" :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro"
                                :necesario-por-diametro="$necesarioPorDiametro" :total-general="$totalGeneral" :consumo-origen="$consumoOrigen" :consumos-por-mes="$consumosPorMes"
                                :producto-base-info="$productoBaseInfo" :stock-por-producto-base="$stockPorProductoBase" :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion"
                                :recomendacion-reposicion="$recomendacionReposicion" :configuracion_vista_stock="$configuracion_vista_stock" />
                        </div>
                    </div>

                    {{-- COLUMNA DERECHA: Carrito (Sticky) --}}
                    <div class="hidden xl:block sticky top-6 self-start">
                        {{-- CARRITO CON ITEMS --}}
                        <div x-show="cart.length > 0" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-x-10"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            class="bg-slate-900 rounded-[1.5rem] p-5 text-white shadow-2xl shadow-blue-900/20 border border-slate-800 flex flex-col max-h-[70vh] relative overflow-hidden">
                            {{-- Background Glow --}}
                            <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/10 rounded-full blur-[80px]">
                            </div>

                            <div class="relative z-10 flex flex-col h-full">
                                <div class="flex items-center justify-between mb-4 shrink-0">
                                    <h3 class="text-xl font-black tracking-tight">Borrador</h3>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2.5 py-0.5 bg-blue-500 text-white text-[10px] font-black rounded-full"
                                            x-text="cart.length"></span>
                                        <button @click="cart = []"
                                            class="text-[9px] font-black text-slate-500 uppercase hover:text-white transition-colors">Limpiar</button>
                                    </div>
                                </div>

                                {{-- Cart Items (Scrollable) --}}
                                <div class="flex-1 overflow-y-auto cart-scrollbar pr-1 space-y-2 mb-4 max-h-[35vh]">
                                    <template x-for="item in cart" :key="item.id">
                                        <div
                                            class="p-3 bg-slate-800/40 rounded-xl border border-slate-700/50 hover:bg-slate-800/80 transition-all duration-200">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="text-[11px] font-bold text-slate-200 leading-tight"
                                                            x-text="'Ø' + item.diametro + (item.longitud ? ' x ' + item.longitud + 'm' : '')"></span>
                                                        <span
                                                            class="text-[8px] font-black text-slate-500 uppercase tracking-widest"
                                                            x-text="item.tipo"></span>
                                                    </div>
                                                    <button @click="toggleItem(item)"
                                                        class="text-slate-500 hover:text-rose-400 transition-colors">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <div class="relative">
                                                    <input type="number" x-model.number="item.cantidad"
                                                        class="cart-input w-full bg-slate-950 border border-slate-700 rounded-lg pl-2 pr-8 py-1 text-[10px] font-black text-blue-400 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
                                                        step="100">
                                                    <span
                                                        class="absolute right-2 top-1 text-[8px] font-black text-slate-600">KG</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Totals & Call to Action (Fixed at bottom) --}}
                                <div class="shrink-0 border-t border-slate-800 pt-4 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <div class="flex flex-col">
                                            <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest">
                                                Peso
                                                Total</p>
                                            <div class="flex items-baseline gap-1">
                                                <p class="text-2xl font-black text-white"
                                                    x-text="cartTotal.toLocaleString('es-ES')"></p>
                                                <span class="text-[9px] font-bold text-slate-500 uppercase">kg</span>
                                            </div>
                                        </div>
                                        <div
                                            class="px-2 py-1 bg-slate-800 rounded-lg border border-slate-700/50 flex items-center gap-1.5">
                                            <span class="text-[10px]">🚛</span>
                                            <span class="text-[10px] font-black text-slate-300"
                                                x-text="Math.ceil(cartTotal / 25000)"></span>
                                        </div>
                                    </div>

                                    <button type="button" @click="mostrarConfirmacion(cart)"
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-3.5 rounded-xl shadow-lg shadow-blue-900/20 transition-all transform active:scale-95 flex items-center justify-center gap-2 group/btn">
                                        <span class="text-xs uppercase tracking-wider">Continuar Pedido</span>
                                        <svg class="w-3.5 h-3.5 group-hover/btn:translate-x-0.5 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- CARRITO VACÍO --}}
                        <div x-show="cart.length === 0"
                            class="bg-white rounded-[1.5rem] p-12 text-center border-2 border-dashed border-slate-200">
                            <div
                                class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                                🛒</div>
                            <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Carrito de
                                Reposición
                            </h4>
                            <p class="text-xs text-slate-400 mt-3 font-medium leading-relaxed">Añade productos del
                                análisis
                                de stock para configurar un nuevo pedido de compra.</p>
                        </div>
                    </div>
                </div>
            </div>


            {{-- MODAL COLADAS / BULTOS PARA ACTIVACIÓN --}}
            <div id="modal-coladas-activacion"
                class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm hidden items-center justify-center z-50 transition-all duration-300">
                <div
                    class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl transform transition-all duration-300 overflow-hidden border border-gray-200">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-5 border-b border-slate-600">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Confirmar activación de línea
                        </h3>
                        <p class="text-sm text-slate-300 mt-2">
                            Registrar coladas y bultos asociados (opcional)
                        </p>
                        <p id="modal-linea-info" class="text-sm text-slate-200 mt-1">
                            Selecciona las coladas y bultos de la línea que estás activando antes de confirmar.
                        </p>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        <div class="bg-blue-50 border-l-4 border-blue-500 px-4 py-3 rounded-r mb-5">
                            <p class="text-sm text-blue-800 leading-relaxed">
                                <strong class="font-semibold">Información:</strong> Puedes añadir cero o más coladas y
                                bultos.
                                Si no necesitas registrar información, deja la tabla vacía y confirma la activación.
                            </p>
                        </div>

                        <div class="border border-gray-300 rounded-xl mb-5 shadow-sm bg-white overflow-hidden">
                            <table class="w-full text-sm table-fixed">
                                <colgroup>
                                    <col style="width:30%">
                                    <col style="width:35%">
                                    <col style="width:20%">
                                    <col style="width:15%">
                                </colgroup>
                                <thead class="bg-gradient-to-r from-gray-700 to-gray-800 text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider text-xs">
                                            Colada</th>
                                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider text-xs">
                                            Fabricante</th>
                                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider text-xs">
                                            Bultos</th>
                                        <th
                                            class="px-4 py-3 text-center font-semibold uppercase tracking-wider text-xs whitespace-nowrap">
                                            Acciones</th>
                                    </tr>
                                </thead>
                            </table>
                            <div class="max-h-72 overflow-y-auto">
                                <table class="w-full text-sm table-fixed">
                                    <colgroup>
                                        <col style="width:30%">
                                        <col style="width:35%">
                                        <col style="width:20%">
                                        <col style="width:15%">
                                    </colgroup>
                                    <tbody id="tabla-coladas-body" class="divide-y divide-gray-200">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-6 pt-2">
                            <button type="button" id="btn-agregar-colada"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Añadir colada / bulto
                            </button>
                        </div>

                        {{-- Footer con botones --}}
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                            <button type="button" id="btn-cancelar-coladas"
                                class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-700 text-sm font-medium px-5 py-2.5 rounded-lg border border-gray-300 transition-all duration-200 shadow-sm hover:shadow">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Cancelar
                            </button>
                            <button type="button" id="btn-confirmar-activacion-coladas"
                                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Confirmar activación
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MODAL CONFIRMACIÓN PEDIDO --}}
            <div id="modalConfirmacion"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-[9999] transition-all duration-300">
                <div
                    class="bg-white rounded-[1.5rem] w-full max-w-5xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[95vh] m-4">
                    {{-- Header --}}
                    <div
                        class="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6 text-white flex justify-between items-center shrink-0">
                        <div>
                            <h3 class="text-2xl font-black tracking-tight uppercase">CONFIRMAR PEDIDO</h3>
                            <p class="text-blue-100 text-sm font-medium opacity-80">Revisa los detalles antes de
                                generar la orden de compra</p>
                        </div>
                        <button type="button" onclick="cerrarModalConfirmacion()"
                            class="p-2 hover:bg-white/20 rounded-xl transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form id="formularioPedido" action="{{ route('pedidos.store') }}" method="POST"
                        class="flex flex-col flex-1 overflow-hidden">
                        @csrf

                        <div class="flex-1 overflow-y-auto p-8 space-y-8">
                            {{-- Proveedor --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="fabricante"
                                        class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Fabricante</label>
                                    <select name="fabricante_id" id="fabricante"
                                        onchange="if(this.value) document.getElementById('distribuidor').value = ''"
                                        class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-4 py-3 text-sm font-bold text-gray-800 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                                        <option value="">-- Seleccionar fabricante --</option>
                                        @foreach ($fabricantes as $fabricante)
                                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label for="distribuidor"
                                        class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Distribuidor</label>
                                    <select name="distribuidor_id" id="distribuidor"
                                        onchange="if(this.value) document.getElementById('fabricante').value = ''"
                                        class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-4 py-3 text-sm font-bold text-gray-800 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                                        <option value="">-- Seleccionar distribuidor --</option>
                                        @foreach ($distribuidores as $distribuidor)
                                            <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Ubicación --}}
                            <div class="space-y-4">
                                <label
                                    class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Lugar
                                    de Entrega</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="group">
                                        <div
                                            class="bg-gray-50 p-4 rounded-3xl border border-gray-100 group-focus-within:border-blue-500 transition-all">
                                            <label
                                                class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-tight">Nave
                                                HPR</label>
                                            <select name="obra_id_hpr" id="obra_id_hpr_modal"
                                                class="w-full bg-transparent border-none p-0 text-sm font-black text-gray-800 focus:ring-0 cursor-pointer"
                                                onchange="if(this.value) { document.getElementById('obra_id_externa_modal').value = ''; document.getElementById('obra_manual_modal').value = ''; }">
                                                <option value="">Seleccionar nave</option>
                                                @foreach ($navesHpr as $nave)
                                                    <option value="{{ $nave->id }}">{{ $nave->obra }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="group">
                                        <div
                                            class="bg-gray-50 p-4 rounded-3xl border border-gray-100 group-focus-within:border-blue-500 transition-all">
                                            <label
                                                class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-tight">Obra
                                                Externa</label>
                                            <select name="obra_id_externa" id="obra_id_externa_modal"
                                                class="w-full bg-transparent border-none p-0 text-sm font-black text-gray-800 focus:ring-0 cursor-pointer"
                                                onchange="if(this.value) { document.getElementById('obra_id_hpr_modal').value = ''; document.getElementById('obra_manual_modal').value = ''; }">
                                                <option value="">Seleccionar obra</option>
                                                @foreach ($obrasExternas as $obra)
                                                    <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="group">
                                        <div
                                            class="bg-gray-50 p-4 rounded-3xl border border-gray-100 group-focus-within:border-blue-500 transition-all">
                                            <label
                                                class="block text-[10px] font-black text-gray-400 mb-2 uppercase tracking-tight">Manual
                                                / Libre</label>
                                            <input type="text" name="obra_manual" id="obra_manual_modal"
                                                class="w-full bg-transparent border-none p-0 text-sm font-black text-gray-800 focus:ring-0 placeholder-gray-300"
                                                placeholder="Dirección manual"
                                                oninput="if(this.value) { document.getElementById('obra_id_hpr_modal').value = ''; document.getElementById('obra_id_externa_modal').value = ''; }"
                                                value="{{ old('obra_manual') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabla de líneas --}}
                            <div class="rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-100">
                                        <tr>
                                            <th
                                                class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                Producto</th>
                                            <th
                                                class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                Dimensiones</th>
                                            <th
                                                class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                Peso Total</th>
                                            <th
                                                class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                Fechas de Entrega</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaConfirmacionBody" class="divide-y divide-gray-50"></tbody>
                                </table>
                            </div>

                            <div id="mensajesGlobales" class="space-y-1"></div>
                        </div>

                        {{-- Footer --}}
                        <div class="shrink-0 bg-gray-50 px-8 py-6 flex justify-end gap-4 border-t border-gray-100">
                            <button type="button" onclick="cerrarModalConfirmacion()"
                                class="px-8 py-3 rounded-2xl text-sm font-bold text-gray-500 hover:bg-gray-200 transition-all">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-emerald-500 hover:bg-emerald-600 px-10 py-3 rounded-2xl text-sm font-black text-white shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all">
                                CREAR PEDIDO DE COMPRA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
    @endif

    {{-- ROL OPERARIO --}}
    @if (Auth::user()->rol === 'operario')
        <div class="p-4 w-full max-w-4xl mx-auto">
            <div class="px-4 flex justify-center">
                <form method="GET" action="{{ route('pedidos.index') }}"
                    class="w-full sm:w-2/3 md:w-1/2 lg:w-1/3 flex flex-col sm:flex-row gap-2 mb-6">
                    <x-tabla.input name="codigo" value="{{ request('codigo') }}" class="flex-grow"
                        placeholder="Introduce el código del pedido (ej: PC25/0003)" />
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow transition">
                        🔍 Buscar
                    </button>
                </form>
            </div>

            @php
                $codigo = request('codigo');
                $pedidosFiltrados = $codigo
                    ? \App\Models\Pedido::with('productos')
                        ->where('codigo', 'like', '%' . $codigo . '%')
                        ->orderBy('created_at', 'desc')
                        ->get()
                    : collect();
            @endphp

            @if ($codigo)
                @if ($pedidosFiltrados->isEmpty())
                    <div class="text-red-500 text-sm text-center">
                        No se encontraron pedidos con el código <strong>{{ $codigo }}</strong>.
                    </div>
                @else
                    {{-- Vista móvil --}}
                    <div class="grid gap-4 sm:hidden">
                        @foreach ($pedidosFiltrados as $pedido)
                            <div class="bg-white shadow rounded-lg p-4 text-sm border">
                                <div><span class="font-semibold">Código:</span> {{ $pedido->codigo }}</div>
                                <div><span class="font-semibold">Fabricante:</span>
                                    {{ $pedido->fabricante->nombre ?? '—' }}</div>
                                <div><span class="font-semibold">Estado:</span> {{ $pedido->estado ?? '—' }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('pedidos.crearRecepcion', $pedido->id) }}" wire:navigate
                                        class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-xs">
                                        Recepcionar
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Vista escritorio --}}
                    <div class="hidden sm:block bg-white shadow rounded-lg overflow-x-auto mt-4">
                        <table class="w-full border text-sm text-center">
                            <thead class="bg-blue-600 text-white uppercase text-xs">
                                <tr>
                                    <th class="px-3 py-2 border">Código</th>
                                    <th class="px-3 py-2 border">Fabricante</th>
                                    <th class="px-3 py-2 border">Estado</th>
                                    <th class="px-3 py-2 border">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pedidosFiltrados as $pedido)
                                    <tr class="border-b hover:bg-blue-50">
                                        <td class="px-3 py-2">{{ $pedido->codigo }}</td>
                                        <td class="px-3 py-2">{{ $pedido->fabricante->nombre ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $pedido->estado ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('pedidos.recepcion', $pedido->id) }}" wire:navigate
                                                class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-xs">
                                                Recepcionar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endif
    </div>

    {{-- ==================== SCRIPTS ==================== --}}

    {{-- Script: Confirmar completar línea --}}
    <script>
        function confirmarCompletarLinea(form) {
            Swal.fire({
                title: '¿Completar línea?',
                html: 'Se marcará como <b>completada</b> sin recepcionar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, completar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }
    </script>

    {{-- Script: Confirmar cancelación de línea --}}
    <script>
        function confirmarCancelacionLinea(pedidoId, lineaId) {
            Swal.fire({
                title: '¿Cancelar línea?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6b7280',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Volver',
            }).then((result) => {
                if (result.isConfirmed) {
                    const formulario = document.querySelector(
                        `.form-cancelar-linea[data-pedido-id="${pedidoId}"][data-linea-id="${lineaId}"]`
                    );
                    if (formulario) {
                        formulario.submit();
                    }
                }
            });
        }
    </script>

    {{-- Script: Limpiar campos del modal --}}
    <script>
        function limpiarObraManual() {
            document.getElementById('obra_manual_modal').value = '';
        }

        function limpiarSelectsObra() {
            document.getElementById('obra_id_hpr_modal').selectedIndex = 0;
            document.getElementById('obra_id_externa_modal').selectedIndex = 0;
        }
    </script>

    {{-- Script: Edición unificada de línea --}}
    <script>
        // ========== EDICIÓN UNIFICADA DE LÍNEA (LUGAR + PRODUCTO) ==========

        function abrirEdicionLinea(lineaId) {
            const lugarView = document.querySelector(`.lugar-entrega-view-${lineaId}`);
            const productoView = document.querySelector(`.producto-view-${lineaId}`);
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);

            if (lugarView) lugarView.classList.add('hidden');
            if (productoView) productoView.classList.add('hidden');
            if (lugarEdit) lugarEdit.classList.remove('hidden');
            if (productoEdit) productoEdit.classList.remove('hidden');

            if (btnEditar) btnEditar.classList.add('hidden');
            if (btnGuardar) btnGuardar.classList.remove('hidden');
            if (btnCancelar) btnCancelar.classList.remove('hidden');
            if (botonesEstado) botonesEstado.classList.add('hidden');

            if (lugarEdit) {
                configurarSelectsLugar(lugarEdit);
            }
        }

        function cancelarEdicionLinea(lineaId) {
            const lugarView = document.querySelector(`.lugar-entrega-view-${lineaId}`);
            const productoView = document.querySelector(`.producto-view-${lineaId}`);
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);

            if (lugarView) lugarView.classList.remove('hidden');
            if (productoView) productoView.classList.remove('hidden');
            if (lugarEdit) lugarEdit.classList.add('hidden');
            if (productoEdit) productoEdit.classList.add('hidden');

            if (btnEditar) btnEditar.classList.remove('hidden');
            if (btnGuardar) btnGuardar.classList.add('hidden');
            if (btnCancelar) btnCancelar.classList.add('hidden');
            if (botonesEstado) botonesEstado.classList.remove('hidden');
        }

        function guardarLinea(lineaId, pedidoId) {
            const lugarEdit = document.querySelector(`.lugar-entrega-edit-${lineaId}`);
            const selectHpr = lugarEdit.querySelector('.obra-hpr-select');
            const selectExterna = lugarEdit.querySelector('.obra-externa-select');
            const inputManual = lugarEdit.querySelector('.obra-manual-input');

            const obraHpr = selectHpr.value;
            const obraExterna = selectExterna.value;
            const obraManual = inputManual.value.trim();
            const totalSeleccionado = [obraHpr, obraExterna, obraManual].filter(v => v).length;

            if (totalSeleccionado === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debes seleccionar un lugar de entrega'
                });
                return;
            }

            if (totalSeleccionado > 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Solo puedes seleccionar una opción de lugar de entrega'
                });
                return;
            }

            const productoEdit = document.querySelector(`.producto-edit-${lineaId}`);
            const selectProducto = productoEdit.querySelector('.producto-base-select');
            const productoBaseId = selectProducto.value;

            if (!productoBaseId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debes seleccionar un producto'
                });
                return;
            }

            const datos = {
                linea_id: lineaId,
                obra_id: obraHpr || obraExterna || null,
                obra_manual: obraManual || null,
                producto_base_id: productoBaseId
            };

            fetch(`/pedidos/${pedidoId}/actualizar-linea`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(datos)
                })
                .then(response => {
                    // Verificar si la respuesta es JSON válido
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Respuesta no es JSON:', text);
                            throw new Error('La respuesta del servidor no es JSON válido');
                        });
                    }

                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || `Error del servidor: ${response.status}`);
                        });
                    }

                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: 'Línea actualizada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al actualizar la línea'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Error al actualizar la línea'
                    });
                });
        }

        function configurarSelectsLugar(editDiv) {
            const selectHpr = editDiv.querySelector('.obra-hpr-select');
            const selectExterna = editDiv.querySelector('.obra-externa-select');
            const inputManual = editDiv.querySelector('.obra-manual-input');

            if (!selectHpr || !selectExterna || !inputManual) return;

            const newSelectHpr = selectHpr.cloneNode(true);
            const newSelectExterna = selectExterna.cloneNode(true);
            const newInputManual = inputManual.cloneNode(true);

            selectHpr.parentNode.replaceChild(newSelectHpr, selectHpr);
            selectExterna.parentNode.replaceChild(newSelectExterna, selectExterna);
            inputManual.parentNode.replaceChild(newInputManual, inputManual);

            newSelectHpr.addEventListener('change', function() {
                if (this.value) {
                    newSelectExterna.value = '';
                    newInputManual.value = '';
                }
            });

            newSelectExterna.addEventListener('change', function() {
                if (this.value) {
                    newSelectHpr.value = '';
                    newInputManual.value = '';
                }
            });

            newInputManual.addEventListener('input', function() {
                if (this.value.trim()) {
                    newSelectHpr.value = '';
                    newSelectExterna.value = '';
                }
            });
        }
    </script>

    {{-- Script: Modal de creación de pedidos y sugerencia de pedido global --}}
    <script>
        function debounce(fn, delay) {
            let timer;
            return function() {
                clearTimeout(timer);
                const args = arguments;
                const context = this;
                timer = setTimeout(() => fn.apply(context, args), delay);
            }
        }

        // Recolectar todas las líneas del modal
        function recolectarLineas() {
            const lineas = [];
            let globalIndex = 0;

            document.querySelectorAll('#tablaConfirmacionBody tr').forEach((tr) => {
                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                if (!contenedorFechas) return;

                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                const inputsPeso = contenedorFechas.querySelectorAll('input[type="hidden"][name*="[peso]"]');

                inputsPeso.forEach((pesoInput, subIndex) => {
                    const peso = parseFloat(pesoInput.value || 0);
                    if (peso <= 0) return;

                    lineas.push({
                        index: globalIndex++,
                        clave: clave,
                        cantidad: peso,
                        sublinea: subIndex + 1
                    });
                });
            });

            return lineas;
        }

        // Sugerir pedidos globales disponibles
        function dispararSugerirMultiple() {
            const fabricante = document.getElementById('fabricante').value;
            const distribuidor = document.getElementById('distribuidor').value;
            if (!fabricante && !distribuidor) return;

            const lineas = recolectarLineas();
            if (lineas.length === 0) return;

            fetch('{{ route('pedidos.verSugerir-pedido-global') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        fabricante_id: fabricante,
                        distribuidor_id: distribuidor,
                        lineas: lineas
                    })
                })
                .then(r => r.json())
                .then(data => {
                    const mensajesGlobales = document.getElementById('mensajesGlobales');
                    mensajesGlobales.innerHTML = '';

                    // Limpiar asignaciones previas
                    document.querySelectorAll('[class*="pg-asignacion-"]').forEach(div => {
                        div.innerHTML = '<span class="text-gray-400">Sin asignar</span>';
                    });

                    if (data.mensaje) {
                        const div = document.createElement('div');
                        div.className = 'text-yellow-700 font-medium';
                        div.textContent = data.mensaje;
                        mensajesGlobales.appendChild(div);
                    }

                    // Procesar asignaciones
                    (data.asignaciones || []).forEach(asig => {
                        if (asig.linea_index !== null && asig.linea_index !== undefined) {
                            let encontrado = false;
                            let globalIdx = 0;

                            document.querySelectorAll('#tablaConfirmacionBody tr').forEach((tr) => {
                                if (encontrado) return;

                                const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                                if (!contenedorFechas) return;

                                const clave = contenedorFechas.id.replace('fechas-camion-', '');
                                const inputsPeso = contenedorFechas.querySelectorAll(
                                    'input[type="hidden"][name*="[peso]"]');

                                inputsPeso.forEach((pesoInput, subIdx) => {
                                    if (encontrado) return;

                                    if (globalIdx === asig.linea_index) {
                                        encontrado = true;

                                        const divAsignacion = document.querySelector(
                                            `.pg-asignacion-${clave}-${subIdx}`);

                                        if (divAsignacion) {
                                            if (asig.codigo) {
                                                divAsignacion.innerHTML = `
                                            <div class="text-left">
                                                <div class="font-bold text-green-700 text-sm">${asig.codigo}</div>
                                                <div class="text-xs text-gray-600 mt-1">${asig.mensaje}</div>
                                                <div class="text-xs text-blue-600 mt-1 font-medium">
                                                    📦 Quedan ${asig.cantidad_restante.toLocaleString('es-ES')} kg
                                                </div>
                                            </div>
                                        `;
                                                divAsignacion.className =
                                                    `pg-asignacion-${clave}-${subIdx} text-xs p-2 bg-green-50 rounded border border-green-200 min-h-[60px]`;

                                                // Agregar input hidden para pedido_global_id
                                                const lineaCamion = document.getElementById(
                                                    `linea-camion-${clave}-${subIdx}`);
                                                if (lineaCamion) {
                                                    let inputPG = lineaCamion.querySelector(
                                                        `input[name="productos[${clave}][${subIdx + 1}][pedido_global_id]"]`
                                                    );
                                                    if (!inputPG) {
                                                        inputPG = document.createElement(
                                                            'input');
                                                        inputPG.type = 'hidden';
                                                        inputPG.name =
                                                            `productos[${clave}][${subIdx + 1}][pedido_global_id]`;
                                                        lineaCamion.appendChild(inputPG);
                                                    }
                                                    inputPG.value = asig.pedido_global_id;
                                                }
                                            } else {
                                                divAsignacion.innerHTML =
                                                    `<div class="text-red-600 text-left">${asig.mensaje}</div>`;
                                                divAsignacion.className =
                                                    `pg-asignacion-${clave}-${subIdx} text-xs p-2 bg-red-50 rounded border border-red-200 min-h-[60px]`;
                                            }
                                        }
                                    }

                                    globalIdx++;
                                });
                            });
                        } else if (asig.mensaje) {
                            const div = document.createElement('div');
                            div.className = 'text-yellow-700 font-medium';
                            div.textContent = asig.mensaje;
                            mensajesGlobales.appendChild(div);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error al sugerir pedido global:', error);
                });
        }

        // Mostrar modal de confirmación
        function mostrarConfirmacion(items = null) {
            // Asegurarse de que el modal sea visible
            const modal = document.getElementById('modalConfirmacion');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            const tbody = document.getElementById('tablaConfirmacionBody');
            tbody.innerHTML = '';

            // Si no nos pasan items (llamada desde fuera de Alpine), los buscamos en el DOM
            if (!items) {
                items = Array.from(document.querySelectorAll('input[type="checkbox"]:checked')).map(cb => {
                    const clave = cb.value;
                    return {
                        id: clave,
                        tipo: document.querySelector(`input[name="detalles[${clave}][tipo]"]`)?.value,
                        diametro: document.querySelector(`input[name="detalles[${clave}][diametro]"]`)?.value,
                        cantidad: document.querySelector(`input[name="detalles[${clave}][cantidad]"]`)?.value,
                        longitud: document.querySelector(`input[name="detalles[${clave}][longitud]"]`)?.value,
                        base_id: document.querySelector(`input[name="detalles[${clave}][producto_base_id]"]`)?.value
                    };
                }).filter(i => i.tipo && i.diametro);
            }

            items.forEach((item) => {
                const clave = item.id;
                const tipo = item.tipo;
                const diametro = item.diametro;
                const cantidad = parseFloat(item.cantidad);
                const longitud = item.longitud;
                const base_id = item.base_id;

                const fila = document.createElement('tr');
                fila.className = "hover:bg-gray-50/50 transition-colors duration-200";

                fila.innerHTML = `
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-700 uppercase tracking-widest border border-slate-200/50">
                        ${tipo}
                    </span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap text-sm font-bold text-gray-900">
                    Ø${diametro} mm ${longitud ? `<span class="text-gray-400 font-medium ml-1">x ${longitud} m</span>` : ''}
                </td>
                <td class="px-6 py-5 whitespace-nowrap w-40">
                    <div class="flex items-center gap-2 group">
                        <input type="number" class="peso-total w-24 bg-gray-100 border border-gray-300 rounded-xl px-3 py-2 text-sm font-black text-gray-900 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white transition-all outline-none"
                               name="detalles[${clave}][cantidad]" value="${cantidad}" step="500" min="0">
                        <span class="text-xs font-black text-gray-500">kg</span>
                    </div>
                </td>
                <td class="px-6 py-5 min-w-[300px]">
                    <div class="fechas-camion flex flex-col gap-3 w-full" id="fechas-camion-${clave}"></div>
                </td>
                <input type="hidden" name="seleccionados[]" value="${clave}">
                <input type="hidden" name="detalles[${clave}][tipo]" value="${tipo}">
                <input type="hidden" name="detalles[${clave}][diametro]" value="${diametro}">
                <input type="hidden" name="detalles[${clave}][producto_base_id]" value="${base_id || ''}">
                ${longitud ? `<input type="hidden" name="detalles[${clave}][longitud]" value="${longitud}">` : ''}
            `;
                tbody.appendChild(fila);

                const inputPeso = fila.querySelector('.peso-total');
                generarFechasPorPeso(inputPeso, clave);
            });

            dispararSugerirMultiple();
        }

        // Generar inputs de fecha según el peso
        function generarFechasPorPeso(input, clave) {
            const peso = parseFloat(input.value || 0);
            const contenedorFechas = document.getElementById(`fechas-camion-${clave}`);
            if (!contenedorFechas) return;

            contenedorFechas.innerHTML = '';
            if (peso <= 0) return;

            const bloques = Math.ceil(peso / 25000);
            for (let i = 0; i < bloques; i++) {
                const pesoBloque = Math.min(25000, peso - i * 25000);

                const lineaCamion = document.createElement('div');
                lineaCamion.className =
                    'flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 bg-gray-50/50 rounded-2xl border border-gray-100 group transition-all hover:bg-white hover:shadow-sm';
                lineaCamion.id = `linea-camion-${clave}-${i}`;

                lineaCamion.innerHTML = `
                <div class="flex-1 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-black">
                        ${i + 1}
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">Camión ${i + 1}</p>
                        <p class="text-xs font-bold text-gray-700">${pesoBloque.toLocaleString('es-ES')} <span class="text-[10px] opacity-50 font-medium">kg</span></p>
                    </div>
                </div>
                <div class="w-full sm:w-auto">
                    <input type="date" 
                           name="productos[${clave}][${i + 1}][fecha]" 
                           required 
                           class="w-full sm:w-40 bg-white border border-gray-200 rounded-xl px-4 py-2 text-xs font-bold text-gray-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                    <input type="hidden" 
                           name="productos[${clave}][${i + 1}][peso]" 
                           value="${pesoBloque}">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <div class="pg-asignacion-${clave}-${i} text-[10px] font-bold text-gray-400 uppercase tracking-tight flex items-center justify-center p-2 bg-white/50 rounded-xl border border-gray-100 min-h-[50px] text-center">
                        Sin asignar
                    </div>
                </div>
            `;

                contenedorFechas.appendChild(lineaCamion);
            }
        }

        // Cerrar modal
        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacion').classList.add('hidden');
            document.getElementById('modalConfirmacion').classList.remove('flex');
        }

        // Event listeners
        function initModalPedidoListeners() {
            // Listener para cambios en peso (solo registrar una vez usando flag en document)
            if (!document._pedidosInputListenerAdded) {
                document._pedidosInputListenerAdded = true;
                document.addEventListener('input', debounce((ev) => {
                    const inputPeso = ev.target.closest('.peso-total');
                    if (!inputPeso) return;

                    const tr = inputPeso.closest('tr');
                    const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                    if (!contenedorFechas) return;

                    const clave = contenedorFechas.id.replace('fechas-camion-', '');
                    generarFechasPorPeso(inputPeso, clave);
                    dispararSugerirMultiple();
                }, 300));
            }

            // Listeners para fabricante/distribuidor
            const fabricanteSelect = document.getElementById('fabricante');
            const distribuidorSelect = document.getElementById('distribuidor');

            if (fabricanteSelect && !fabricanteSelect.dataset.initialized) {
                fabricanteSelect.dataset.initialized = 'true';
                fabricanteSelect.addEventListener('change', dispararSugerirMultiple);
            }
            if (distribuidorSelect && !distribuidorSelect.dataset.initialized) {
                distribuidorSelect.dataset.initialized = 'true';
                distribuidorSelect.addEventListener('change', dispararSugerirMultiple);
            }
        }

        // Inicializar en carga normal y después de navegación con wire:navigate
        document.addEventListener('DOMContentLoaded', initModalPedidoListeners);
        document.addEventListener('livewire:navigated', initModalPedidoListeners);
    </script>

    {{-- Script: Validación formulario pedido --}}
    <script>
        function initFormularioPedidoValidacion() {
            const formulario = document.getElementById('formularioPedido');
            if (!formulario || formulario.dataset.initialized) {
                return;
            }
            formulario.dataset.initialized = 'true';

            formulario.addEventListener('submit', function(ev) {
                ev.preventDefault();
                const errores = [];

                const fabricante = document.getElementById('fabricante').value;
                const distribuidor = document.getElementById('distribuidor').value;
                if (!fabricante && !distribuidor) {
                    errores.push('Debes seleccionar un fabricante o un distribuidor.');
                }
                if (fabricante && distribuidor) {
                    errores.push('Solo puedes seleccionar uno: fabricante o distribuidor.');
                }

                const obraHpr = document.getElementById('obra_id_hpr_modal').value;
                const obraExterna = document.getElementById('obra_id_externa_modal').value;
                const obraManual = document.getElementById('obra_manual_modal').value.trim();
                const totalObras = [obraHpr, obraExterna, obraManual].filter(v => v && v !== '').length;
                if (totalObras === 0) {
                    errores.push('Debes seleccionar una nave, obra externa o escribir un lugar de entrega.');
                }
                if (totalObras > 1) {
                    errores.push(
                        'Solo puedes seleccionar una opción: nave, obra externa o introducirla manualmente.');
                }

                const resumenLineas = [];
                document.querySelectorAll('#tablaConfirmacionBody tr').forEach(tr => {
                    const tipo = tr.querySelector('td:nth-child(1)')?.textContent.trim();
                    const diametro = tr.querySelector('td:nth-child(2)')?.textContent.trim().replace(' mm',
                            '')
                        .split('/')[0].trim();
                    const peso = parseFloat(tr.querySelector('.peso-total')?.value || 0);

                    if (tipo && diametro) {
                        if (peso <= 0) {
                            errores.push(`El peso de la línea ${tipo} ${diametro} debe ser mayor a 0.`);
                        }

                        const contenedorFechas = tr.querySelector('[id^="fechas-camion-"]');
                        const fechas = [];

                        if (contenedorFechas) {
                            const inputsFecha = contenedorFechas.querySelectorAll('input[type="date"]');
                            inputsFecha.forEach((input, idx) => {
                                if (!input.value) {
                                    errores.push(
                                        `Completa la fecha del camión ${idx + 1} para ${tipo} Ø${diametro}.`
                                    );
                                }
                                fechas.push(input.value || '—');
                            });
                        }

                        resumenLineas.push({
                            tipo,
                            diametro,
                            peso,
                            fechas
                        });
                    }
                });

                if (resumenLineas.length === 0) {
                    errores.push('Debes seleccionar al menos un producto para generar el pedido.');
                }

                if (errores.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Revisa los datos',
                        html: '<ul style="text-align:left;">' + errores.map(e => `<li>• ${e}</li>`).join(
                                '') +
                            '</ul>'
                    });
                    return false;
                }

                let proveedorTexto = fabricante ?
                    `Fabricante: ${document.querySelector('#fabricante option:checked').textContent}` :
                    `Distribuidor: ${document.querySelector('#distribuidor option:checked').textContent}`;

                let obraTexto = obraHpr ?
                    `Nave: ${document.querySelector('#obra_id_hpr_modal option:checked').textContent}` :
                    obraExterna ?
                    `Obra externa: ${document.querySelector('#obra_id_externa_modal option:checked').textContent}` :
                    `Lugar manual: ${obraManual}`;

                let htmlResumen =
                    `<p><b>${proveedorTexto}</b></p><p><b>${obraTexto}</b></p><hr><ul style="text-align:left;">`;
                resumenLineas.forEach(l => {
                    htmlResumen +=
                        `<li>• ${l.tipo} Ø${l.diametro} → ${l.peso.toLocaleString('es-ES')} kg<br>` +
                        `📅 Fechas de entrega: ${l.fechas.join(', ')}</li>`;
                });
                htmlResumen += '</ul>';

                Swal.fire({
                    title: '¿Crear pedido de compra?',
                    html: htmlResumen,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, crear pedido',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#16a34a',
                    focusCancel: true,
                    width: 600,
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        ev.target.submit();
                    }
                });
            });
        }

        // Inicializar en carga normal y después de navegación con wire:navigate
        document.addEventListener('DOMContentLoaded', initFormularioPedidoValidacion);
        document.addEventListener('livewire:navigated', initFormularioPedidoValidacion);
    </script>
    <script>
        function initStockSelect() {
            const selectObra = document.getElementById('obra_id_hpr_stock');
            const contenedorStock = document.getElementById('contenedor-stock');
            const loadingIndicator = document.getElementById('loading-stock');

            if (!selectObra || !contenedorStock) {
                return;
            }

            // Evitar registrar el listener múltiples veces
            if (selectObra.dataset.initialized) {
                return;
            }
            selectObra.dataset.initialized = 'true';

            selectObra.addEventListener('change', function() {
                const obraId = this.value;

                // Mostrar loading
                if (loadingIndicator) loadingIndicator.classList.remove('hidden');
                contenedorStock.style.opacity = '0.5';
                contenedorStock.style.pointerEvents = 'none';

                // URL de la petición
                const url = '{{ route('pedidos.verStockHtml') }}' + (obraId ? '?obra_id_hpr=' + obraId :
                    '');

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Error en la petición');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.html) {
                            contenedorStock.innerHTML = data.html;

                            // Re-inicializar Alpine en el nuevo contenido
                            if (window.Alpine) {
                                window.Alpine.initTree(contenedorStock);
                            }

                            contenedorStock.style.opacity = '1';
                        } else {
                            throw new Error(data.message || 'Error desconocido');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar la tabla de stock',
                            confirmButtonColor: '#3b82f6'
                        });
                        contenedorStock.style.opacity = '1';
                    })
                    .finally(() => {
                        if (loadingIndicator) loadingIndicator.classList.add('hidden');
                        contenedorStock.style.pointerEvents = 'auto';
                    });
            });
        }

        // Inicializar en carga normal y después de navegación con wire:navigate
        document.addEventListener('DOMContentLoaded', initStockSelect);
        document.addEventListener('livewire:navigated', initStockSelect);
    </script>
    <script>
        function initColadasModal() {
            const CLIENTE_ID_REQUIERE_COLADAS = 1;
            const BASE_PEDIDOS_URL = `{{ url('/pedidos') }}`;
            const CLASES_ESTADO_A_REMOVER = [
                'bg-yellow-100',
                'bg-green-500',
                'bg-green-100',
                'bg-gray-300',
                'text-gray-500',
                'opacity-70',
                'cursor-not-allowed',
                'even:bg-gray-50',
                'odd:bg-white',
                'bg-white',
                'bg-gray-50'
            ];
            const CLASES_PENDIENTE = ['even:bg-gray-50', 'odd:bg-white'];

            const modal = document.getElementById('modal-coladas-activacion');
            const cuerpoTabla = document.getElementById('tabla-coladas-body');
            const modalLineaInfo = document.getElementById('modal-linea-info');
            const btnAgregar = document.getElementById('btn-agregar-colada');
            const btnCancelar = document.getElementById('btn-cancelar-coladas');
            const btnConfirmar = document.getElementById('btn-confirmar-activacion-coladas');

            // Lista de fabricantes para el select
            const fabricantesDisponibles = @json($fabricantes->map(fn($f) => ['id' => $f->id, 'nombre' => $f->nombre]));

            let pedidoIdActual = null;
            let lineaIdActual = null;
            let formularioActivacionActual = null;

            function obtenerFilaLinea(pedidoId, lineaId) {
                return document.querySelector(
                    `.fila-pedido-linea[data-pedido-id="${pedidoId}"][data-linea-id="${lineaId}"]`);
            }

            function actualizarLineaInfoEnModal(pedidoId, lineaId) {
                if (!modalLineaInfo) {
                    return;
                }

                const fila = obtenerFilaLinea(pedidoId, lineaId);
                if (!fila) {
                    modalLineaInfo.textContent = 'Línea seleccionada: no disponible';
                    return;
                }

                const codigo = fila.dataset.lineaCodigo ? `Línea ${fila.dataset.lineaCodigo}` : 'Línea';
                const producto = fila.dataset.lineaProducto ? fila.dataset.lineaProducto : null;
                const diametro = fila.dataset.lineaDiametro ? `Ø${fila.dataset.lineaDiametro}` : null;
                const longitud = fila.dataset.lineaLongitud ? `x${fila.dataset.lineaLongitud.trim()} m` : null;
                const cantidad = fila.dataset.lineaCantidad ?
                    `${parseFloat(fila.dataset.lineaCantidad).toLocaleString('es-ES', {maximumFractionDigits: 2})} kg` :
                    null;

                const detalles = [producto, diametro, longitud, cantidad].filter(Boolean).join(' • ');
                modalLineaInfo.textContent = detalles ? `${codigo} • ${detalles}` : codigo;
            }

            function actualizarEstadoVisualLinea(pedidoId, lineaId, nuevoEstado, clasesAgregar = [], filaElement =
                null) {
                const fila = filaElement || obtenerFilaLinea(pedidoId, lineaId);
                if (!fila) {
                    console.warn(`Fila no encontrada para pedido ${pedidoId} linea ${lineaId}`);
                    return;
                }

                // Remover clases una por una
                CLASES_ESTADO_A_REMOVER.forEach(clase => {
                    fila.classList.remove(clase);
                });

                // Agregar nuevas clases
                clasesAgregar.forEach(clase => {
                    if (clase) {
                        fila.classList.add(clase);
                    }
                });

                const estadoCelda = fila.querySelector('[data-columna-estado]');
                if (estadoCelda) {
                    estadoCelda.textContent = nuevoEstado;
                }
            }

            function toggleBotonesLinea(fila) {
                const formActivar = fila.querySelector('.form-activar-linea');
                const formDesactivar = fila.querySelector('.form-desactivar-linea');

                if (formActivar) formActivar.classList.toggle('hidden');
                if (formDesactivar) formDesactivar.classList.toggle('hidden');
            }

            function desactivarLinea(form) {
                const pedidoId = form.getAttribute('data-pedido-id');
                const lineaId = form.getAttribute('data-linea-id');
                if (!pedidoId || !lineaId) {
                    form.submit();
                    return;
                }

                const formData = new FormData(form);
                if (!formData.has('_token')) {
                    formData.append('_token', obtenerTokenCsrf());
                }
                if (!formData.has('_method')) {
                    formData.append('_method', 'DELETE');
                }

                fetch(form.getAttribute('action'), {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || !data.success) {
                            const mensaje = data && data.message ? data.message :
                                'Error al desactivar la línea.';
                            throw new Error(mensaje);
                        }

                        const fila = form.closest('tr');
                        actualizarEstadoVisualLinea(pedidoId, lineaId, 'pendiente', CLASES_PENDIENTE, fila);
                        toggleBotonesLinea(fila);

                        Swal.fire({
                            icon: 'success',
                            title: data.message || 'Línea desactivada correctamente.',
                            showConfirmButton: false,
                            timer: 1800,
                        });
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Error al desactivar la línea.',
                        });
                    });
            }

            function abrirModalColadas(pedidoId, lineaId, form = null) {
                pedidoIdActual = pedidoId;
                lineaIdActual = lineaId;
                formularioActivacionActual = form;

                cuerpoTabla.innerHTML = '';
                agregarFilaColada();
                actualizarLineaInfoEnModal(pedidoId, lineaId);

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModalColadas(limpiarFormulario = false) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                pedidoIdActual = null;
                lineaIdActual = null;
                if (limpiarFormulario) {
                    formularioActivacionActual = null;
                }
            }

            function generarSelectFabricantes() {
                let options = '<option value="">Seleccionar...</option>';
                fabricantesDisponibles.forEach(fab => {
                    options += `<option value="${fab.id}">${fab.nombre}</option>`;
                });
                return options;
            }

            function agregarFilaColada() {
                const fila = document.createElement('tr');
                fila.className = 'fila-colada hover:bg-gray-50 transition-colors duration-150';
                fila.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="text" class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm input-colada transition-all duration-200 outline-none" placeholder="Ej: 12/3456">
                    </td>
                    <td class="px-4 py-3">
                        <select class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm input-fabricante transition-all duration-200 outline-none">
                            ${generarSelectFabricantes()}
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" step="1" min="0" class="w-full border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg px-3 py-2 text-sm input-bulto transition-all duration-200 outline-none" placeholder="0">
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" class="btn-eliminar-colada inline-flex items-center justify-center bg-red-500 hover:bg-red-600 active:bg-red-700 text-white rounded-lg w-8 h-8 transition-all duration-200 shadow-sm hover:shadow transform hover:scale-105" title="Eliminar fila">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </td>
                `;
                cuerpoTabla.appendChild(fila);
            }

            if (btnAgregar && !btnAgregar.dataset.initialized) {
                btnAgregar.dataset.initialized = 'true';
                btnAgregar.addEventListener('click', function() {
                    agregarFilaColada();
                });
            }

            if (cuerpoTabla && !cuerpoTabla.dataset.initialized) {
                cuerpoTabla.dataset.initialized = 'true';
                cuerpoTabla.addEventListener('click', function(ev) {
                    const botonEliminar = ev.target.closest('.btn-eliminar-colada');
                    if (botonEliminar) {
                        const fila = botonEliminar.closest('tr');
                        if (fila) {
                            fila.remove();
                        }
                    }
                });
            }

            if (btnCancelar && !btnCancelar.dataset.initialized) {
                btnCancelar.dataset.initialized = 'true';
                btnCancelar.addEventListener('click', function() {
                    cerrarModalColadas(true);
                });
            }

            function obtenerTokenCsrf() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    return meta.getAttribute('content');
                }
                const input = document.querySelector('input[name="_token"]');
                return input ? input.value : '';
            }

            function activarLineaConColadas() {
                if (!pedidoIdActual || !lineaIdActual) {
                    return;
                }

                const filas = cuerpoTabla.querySelectorAll('.fila-colada');
                const coladas = [];

                filas.forEach(fila => {
                    const coladaInput = fila.querySelector('.input-colada');
                    const fabricanteSelect = fila.querySelector('.input-fabricante');
                    const bultoInput = fila.querySelector('.input-bulto');
                    const colada = coladaInput ? coladaInput.value.trim() : '';
                    const fabricanteId = fabricanteSelect ? fabricanteSelect.value : '';
                    const bultoValor = bultoInput ? bultoInput.value.trim() : '';

                    if (colada !== '' || bultoValor !== '') {
                        const bulto = bultoValor !== '' ? parseFloat(bultoValor.replace(',', '.')) : null;
                        coladas.push({
                            colada: colada !== '' ? colada : null,
                            fabricante_id: fabricanteId !== '' ? parseInt(fabricanteId) : null,
                            bulto: bulto,
                        });
                    }
                });

                const url = `{{ url('/pedidos') }}/${pedidoIdActual}/lineas/${lineaIdActual}/activar-con-coladas`;

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': obtenerTokenCsrf(),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            coladas
                        }),
                    })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        status: response.status,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || !data.success) {
                            const mensaje = data && data.message ? data.message : 'Error al activar la línea.';
                            throw new Error(mensaje);
                        }

                        cerrarModalColadas();

                        let form = formularioActivacionActual;
                        if (!form) {
                            const formSelector =
                                `.form-activar-linea[data-pedido-id="${pedidoIdActual}"][data-linea-id="${lineaIdActual}"]`;
                            form = document.querySelector(formSelector);
                        }

                        if (form) {
                            const fila = form.closest('tr');
                            actualizarEstadoVisualLinea(pedidoIdActual, lineaIdActual, 'activo', [
                                'bg-yellow-100'
                            ], fila);
                            toggleBotonesLinea(fila);
                            formularioActivacionActual = null;
                        } else {
                            // Fallback si no encontramos el form, intentamos buscar la fila por ID
                            actualizarEstadoVisualLinea(pedidoIdActual, lineaIdActual, 'activo', [
                                'bg-yellow-100'
                            ]);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: data.message || 'Línea activada correctamente.',
                            showConfirmButton: false,
                            timer: 1800,
                        });
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Error al activar la línea.',
                        });
                    });
            }

            if (btnConfirmar && !btnConfirmar.dataset.initialized) {
                btnConfirmar.dataset.initialized = 'true';
                btnConfirmar.addEventListener('click', function() {
                    activarLineaConColadas();
                });
            }

            // Evitar registrar el listener de submit múltiples veces
            if (document._coladasSubmitListenerAdded) {
                return;
            }
            document._coladasSubmitListenerAdded = true;

            document.addEventListener('submit', function(ev) {
                const form = ev.target.closest('form');
                if (!form) {
                    return;
                }

                console.log('🔍 Submit capturado:', {
                    formClasses: form.className,
                    hasActivarClass: form.classList.contains('form-activar-linea')
                });

                if (form.classList.contains('form-desactivar-linea')) {
                    ev.preventDefault();
                    ev.stopImmediatePropagation();
                    desactivarLinea(form);
                    return;
                }

                if (form.classList.contains('form-activar-linea')) {
                    const clienteId = parseInt(form.getAttribute('data-cliente-id') || '0', 10);

                    console.log('✅ Formulario activar detectado:', {
                        clienteId: clienteId,
                        CLIENTE_ID_REQUIERE_COLADAS: CLIENTE_ID_REQUIERE_COLADAS,
                        coincide: clienteId === CLIENTE_ID_REQUIERE_COLADAS
                    });

                    if (clienteId === CLIENTE_ID_REQUIERE_COLADAS) {
                        console.log('🎯 Abriendo modal de coladas...');
                        ev.preventDefault();
                        ev.stopImmediatePropagation();
                        const pedidoId = form.getAttribute('data-pedido-id');
                        const lineaId = form.getAttribute('data-linea-id');
                        abrirModalColadas(pedidoId, lineaId, form);
                    } else {
                        console.log('⚠️ Cliente ID no coincide, dejando submit normal');
                    }
                }
            }, true);
        }

        // Inicializar en carga normal y después de navegación con wire:navigate
        document.addEventListener('DOMContentLoaded', initColadasModal);
        document.addEventListener('livewire:navigated', initColadasModal);
    </script>

    {{-- Inicialización maestra con patrón robusto --}}
    <script>
        function initPedidosPage() {
            // Prevenir doble inicialización
            if (document.body.dataset.pedidosPageInit === 'true') return;

            console.log('🔍 Inicializando página de Pedidos...');

            // Llamar a todas las funciones de inicialización
            if (typeof initModalPedidoListeners === 'function') initModalPedidoListeners();
            if (typeof initFormularioPedidoValidacion === 'function') initFormularioPedidoValidacion();
            if (typeof initStockSelect === 'function') initStockSelect();
            if (typeof initColadasModal === 'function') initColadasModal();

            // Marcar como inicializado
            document.body.dataset.pedidosPageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers.push(initPedidosPage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initPedidosPage);
        document.addEventListener('DOMContentLoaded', initPedidosPage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.pedidosPageInit = 'false';
        });
    </script>
</x-app-layout>
