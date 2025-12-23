<x-app-layout>
    <x-slot name="title">Pedidos - {{ config('app.name') }}</x-slot>

    <style>
        /* Hide increment/decrement buttons on number inputs */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>

    <div class="max-w-[1600px] mx-auto px-4 py-8" x-data="{
        activeTab: 'active',
        cart: [],
        addToCart(item) {
            const exists = this.cart.find(i => i.id === item.id);
            if (!exists) {
                this.cart.push(item);
                this.notify('Añadido al pedido');
            }
        },
        removeFromCart(id) {
            this.cart = this.cart.filter(i => i.id !== id);
        },
        get totalWeight() {
            return this.cart.reduce((sum, item) => {
                const val = parseFloat(item.cantidad);
                return sum + (isNaN(val) ? 0 : val);
            }, 0);
        },
        notify(msg) {
            window.dispatchEvent(new CustomEvent('notify', { detail: msg }));
        }
    }">
        @if (auth()->user()->rol === 'oficina')
            {{-- Header de la sección con Tabs Modernos --}}
            <div
                class="flex flex-col xl:flex-row xl:items-end justify-between gap-8 mb-12 border-b border-slate-200 pb-10">
                <div>
                    <h1 class="text-4xl xl:text-5xl font-black text-slate-900 tracking-tight mb-2">Pedidos</h1>
                    <p class="text-lg text-slate-500 font-medium">Gestión integral de suministros y reaprovechamiento.
                    </p>
                </div>

                <nav class="flex bg-slate-800 p-1.5 rounded-2xl border border-slate-200 shadow-inner">
                    <button @click="activeTab = 'active'"
                        :class="activeTab === 'active' ? 'bg-white shadow-sm text-slate-900' :
                            'text-slate-50 hover:text-slate-900 hover:bg-white/50'"
                        class="px-8 py-3.5 rounded-xl text-sm font-black transition-all duration-300 flex items-center gap-3">
                        <span class="text-lg">📋</span>
                        Pedidos Activos
                    </button>
                    <button @click="activeTab = 'replenishment'"
                        :class="activeTab === 'replenishment' ? 'bg-white shadow-sm text-slate-900' :
                            'text-slate-50 hover:text-slate-900 hover:bg-white/50'"
                        class="px-8 py-3.5 rounded-xl text-sm font-black transition-all duration-300 flex items-center gap-3">
                        <span class="text-lg">📈</span>
                        Análisis y Reposición
                        <span x-show="cart.length > 0"
                            class="flex h-2 w-2 rounded-full bg-slate-900 animate-pulse"></span>
                    </button>
                </nav>
            </div>

            <!-- CONTENIDO DE TABS -->
            <div class="flex flex-col lg:flex-row gap-8 items-start">

                <!-- ÁREA PRINCIPAL -->
                <div class="flex-1 w-full min-w-0">

                    <!-- TAB: PEDIDOS ACTIVOS -->
                    <div x-show="activeTab === 'active'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        @livewire('pedidos-table')
                    </div>

                    <!-- TAB: ANÁLISIS Y REPOSICIÓN -->
                    <div x-show="activeTab === 'replenishment'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8">

                        {{-- Filtros de Stock --}}
                        <div
                            class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                            <h2 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                                <span class="p-2.5 bg-blue-50 text-blue-600 rounded-2xl">📊</span>
                                Análisis de Necesidades
                            </h2>

                            <div class="flex items-center gap-4">
                                <div
                                    class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-2xl border border-slate-200">
                                    <label for="obra_id_hpr_stock"
                                        class="pl-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Nave:</label>
                                    <select name="obra_id_hpr" id="obra_id_hpr_stock"
                                        class="border-0 bg-transparent text-sm font-bold text-slate-700 focus:ring-0 rounded-xl pr-10">
                                        <option value="">Todas las naves</option>
                                        @foreach ($obrasHpr as $obra)
                                            <option value="{{ $obra->id }}"
                                                {{ request('obra_id_hpr') == $obra->id ? 'selected' : '' }}>
                                                {{ $obra->obra }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Tabla de Stock con Inyección de Eventos --}}
                        <div class="relative min-h-[400px]" id="contenedor-stock">
                            <div id="loading-stock"
                                class="hidden absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 items-center justify-center rounded-3xl">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="animate-spin h-10 w-10 text-blue-600" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span
                                        class="text-xs font-black text-slate-500 uppercase tracking-widest">Actualizando
                                        Stock...</span>
                                </div>
                            </div>
                            <x-estadisticas.stock :nombre-meses="$nombreMeses" :stock-data="$stockData" :pedidos-por-diametro="$pedidosPorDiametro"
                                :necesario-por-diametro="$necesarioPorDiametro" :total-general="$totalGeneral" :consumo-origen="$consumoOrigen" :consumos-por-mes="$consumosPorMes"
                                :producto-base-info="$productoBaseInfo" :stock-por-producto-base="$stockPorProductoBase" :kg-pedidos-por-producto-base="$kgPedidosPorProductoBase" :resumen-reposicion="$resumenReposicion"
                                :recomendacion-reposicion="$recomendacionReposicion" :configuracion_vista_stock="$configuracion_vista_stock" />
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR: DRAFT ORDER CART -->
                <aside class="w-full lg:w-[400px] sticky top-10"
                    x-show="activeTab === 'replenishment' && cart.length > 0"
                    x-transition:enter="transition ease-out duration-500 translate-x-full"
                    x-transition:enter-start="translate-x-full opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100">
                    <div
                        class="bg-slate-900 rounded-[1.5rem] p-5 text-white shadow-2xl shadow-blue-900/20 border border-slate-800 flex flex-col h-[calc(100vh-120px)] max-h-[540px] relative overflow-hidden">
                        <!-- Background Glow -->
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/10 rounded-full blur-[80px]"></div>

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

                            <!-- Cart Items (Scrollable) -->
                            <div class="flex-1 overflow-y-auto custom-scrollbar pr-1 space-y-2 mb-4">
                                <template x-for="item in cart" :key="item.id">
                                    <div
                                        class="p-3 bg-slate-800/40 rounded-xl border border-slate-700/50 hover:bg-slate-800/80 transition-all duration-200">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-start justify-between">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-bold text-slate-200 leading-tight"
                                                        x-text="item.nombre"></span>
                                                    <span
                                                        class="text-[8px] font-black text-slate-500 uppercase tracking-widest"
                                                        x-text="item.tipo"></span>
                                                </div>
                                                <button @click="removeFromCart(item.id)"
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
                                                    class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-2 pr-8 py-1 text-[10px] font-black text-blue-400 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
                                                    step="100">
                                                <span
                                                    class="absolute right-2 top-1 text-[8px] font-black text-slate-600">KG</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Totals & Call to Action (Fixed at bottom) -->
                            <div class="shrink-0 border-t border-slate-800 pt-4 space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex flex-col">
                                        <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Peso
                                            Total</p>
                                        <div class="flex items-baseline gap-1">
                                            <p class="text-2xl font-black text-white"
                                                x-text="totalWeight.toLocaleString()"></p>
                                            <span class="text-[9px] font-bold text-slate-500 uppercase">kg</span>
                                        </div>
                                    </div>
                                    <div
                                        class="px-2 py-1 bg-slate-800 rounded-lg border border-slate-700/50 flex items-center gap-1.5">
                                        <span class="text-[10px]">🚛</span>
                                        <span class="text-[10px] font-black text-slate-300"
                                            x-text="Math.ceil(totalWeight / 25000)"></span>
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
                </aside>

                <aside class="w-full lg:w-[420px] sticky top-32"
                    x-show="activeTab === 'replenishment' && cart.length === 0">
                    <div class="bg-white rounded-[2.5rem] p-12 text-center border-2 border-dashed border-slate-200">
                        <div
                            class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                            🛒</div>
                        <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Carrito de Reposición
                        </h4>
                        <p class="text-xs text-slate-400 mt-3 font-medium leading-relaxed">Añade productos del análisis
                            de stock para configurar un nuevo pedido de compra.</p>
                    </div>
                </aside>
            </div>

            <!-- Toast de Éxito Alpine -->
            <div x-data="{ show: false, message: '' }"
                @notify.window="message = $event.detail; show = true; setTimeout(() => show = false, 3000)"
                x-show="show" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-12" x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-12"
                class="fixed bottom-10 right-10 z-[100] bg-slate-800 text-white px-6 py-4 rounded-[1.5rem] shadow-2xl border border-slate-700 flex items-center gap-3"
                x-cloak>
                <div class="p-1.5 bg-emerald-500 rounded-lg">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <span class="text-sm font-black tracking-tight" x-text="message"></span>
            </div>

            {{-- MODAL COLADAS / BULTOS PARA ACTIVACIÓN --}}
            <div id="modal-coladas-activacion"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-[60] transition-all duration-300">
                <div
                    class="bg-white rounded-3xl w-full max-w-3xl shadow-2xl transform transition-all duration-300 overflow-hidden border border-slate-200">
                    {{-- Header --}}
                    <div class="bg-slate-800 px-8 py-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-slate-700 rounded-xl">
                                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                Confirmar activación de línea
                            </h3>
                            <button
                                onclick="document.getElementById('modal-coladas-activacion').classList.add('hidden')"
                                class="text-slate-400 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-slate-400 mt-2 ml-14">
                            Registra coladas y bultos asociados para iniciar la producción.
                        </p>
                    </div>

                    {{-- Body --}}
                    <div class="p-8">
                        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-xl mb-6">
                            <div class="flex gap-3">
                                <span class="text-amber-600">⚠️</span>
                                <p class="text-sm text-amber-800 leading-relaxed font-medium">
                                    Puedes añadir coladas y bultos ahora o hacerlo más tarde. Si no necesitas registrar
                                    esta información, pulsa directamente en confirmar.
                                </p>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-2xl mb-6 shadow-sm bg-white overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-bold uppercase tracking-wider text-[10px]">
                                            Colada</th>
                                        <th class="px-4 py-3 text-left font-bold uppercase tracking-wider text-[10px]">
                                            Fabricante</th>
                                        <th class="px-4 py-3 text-left font-bold uppercase tracking-wider text-[10px]">
                                            Bultos</th>
                                        <th
                                            class="px-4 py-3 text-center font-bold uppercase tracking-wider text-[10px]">
                                            Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-coladas-body" class="divide-y divide-slate-100">
                                </tbody>
                            </table>
                            <div id="vacio-coladas" class="p-8 text-center hidden">
                                <p class="text-slate-400 text-sm font-medium">No hay coladas añadidas</p>
                            </div>
                        </div>

                        <div class="flex justify-start mb-8">
                            <button type="button" id="btn-agregar-colada"
                                class="inline-flex items-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-600 text-sm font-bold px-4 py-2.5 rounded-xl transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Añadir colada / bulto
                            </button>
                        </div>

                        {{-- Footer con botones --}}
                        <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                            <button type="button" id="btn-cancelar-coladas"
                                class="btn-secundario text-slate-600 font-bold px-6 py-3 rounded-xl hover:bg-slate-100 transition-all">
                                Cancelar
                            </button>
                            <button type="button" id="btn-confirmar-activacion-coladas"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-green-200 transition-all transform active:scale-95">
                                Confirmar activación
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MODAL CONFIRMACIÓN PEDIDO --}}
            <div id="modalConfirmacion"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden items-center justify-center z-[70] transition-all duration-300">
                <div
                    class="bg-white rounded-[3rem] w-full max-w-6xl shadow-2xl transform transition-all duration-300 overflow-hidden border border-slate-200">

                    {{-- Header --}}
                    <div class="bg-slate-900 px-10 py-8 flex items-center justify-between relative overflow-hidden">
                        <div class="absolute -top-12 -right-12 w-48 h-48 bg-blue-600/20 rounded-full blur-[60px]">
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-2xl font-black text-white tracking-tight">Cerrar Pedido de Compra</h3>
                            <p class="text-slate-400 text-sm font-medium mt-1">Paso 2 de 2: Definición de Logística y
                                Plazos</p>
                        </div>
                        <button onclick="cerrarModalConfirmacion()"
                            class="relative z-10 p-2 text-slate-400 hover:text-white transition-colors hover:bg-white/10 rounded-xl">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form id="formularioPedido" action="{{ route('pedidos.store') }}" method="POST"
                        class="p-10 space-y-8">
                        @csrf

                        <div class="grid lg:grid-cols-2 gap-8">
                            {{-- Columna Izquierda: Entidades --}}
                            <div class="space-y-6">
                                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 flex flex-col gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="p-2 bg-blue-100 text-blue-600 rounded-xl text-lg">🏢</span>
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">
                                            Proveedor / Intermediario</h4>
                                    </div>

                                    <div class="grid gap-4">
                                        <div>
                                            <label for="fabricante"
                                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1 mb-2">Fabricante
                                                (Directo)</label>
                                            <select name="fabricante_id" id="fabricante"
                                                class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-3.5 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                                                <option value="">-- No seleccionado --</option>
                                                @foreach ($fabricantes as $fabricante)
                                                    <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="h-px flex-1 bg-slate-200"></div>
                                            <span class="text-[10px] font-black text-slate-300 uppercase italic">o
                                                bien</span>
                                            <div class="h-px flex-1 bg-slate-200"></div>
                                        </div>

                                        <div>
                                            <label for="distribuidor"
                                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1 mb-2">Distribuidor
                                                Recomendado</label>
                                            <select name="distribuidor_id" id="distribuidor"
                                                class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-3.5 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-50 transition-all outline-none">
                                                <option value="">-- No seleccionado --</option>
                                                @foreach ($distribuidores as $distribuidor)
                                                    <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Columna Derecha: Entrega --}}
                            <div class="space-y-6">
                                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 flex flex-col gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="p-2 bg-emerald-100 text-emerald-600 rounded-xl text-lg">📍</span>
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Lugar
                                            de Entrega</h4>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1 mb-2">Nave
                                                    HPR</label>
                                                <select name="obra_id_hpr" id="obra_id_hpr_modal"
                                                    class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-50 transition-all outline-none"
                                                    onchange="limpiarObraManual()">
                                                    <option value="">Elegir nave...</option>
                                                    @foreach ($navesHpr as $nave)
                                                        <option value="{{ $nave->id }}">{{ $nave->obra }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1 mb-2">Obra
                                                    Externa</label>
                                                <select name="obra_id_externa" id="obra_id_externa_modal"
                                                    class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-50 transition-all outline-none"
                                                    onchange="limpiarObraManual()">
                                                    <option value="">Elegir obra...</option>
                                                    @foreach ($obrasExternas as $obra)
                                                        <option value="{{ $obra->id }}">{{ $obra->obra }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-black text-slate-500 uppercase tracking-widest pl-1 mb-2">Texto
                                                Libre / Otra Ubicación</label>
                                            <input type="text" name="obra_manual" id="obra_manual_modal"
                                                class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-50 transition-all outline-none"
                                                placeholder="Ingresa dirección manual..."
                                                oninput="limpiarSelectsObra()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tabla de Productos --}}
                        <div class="border border-slate-100 rounded-[2rem] overflow-hidden shadow-sm">
                            <div class="overflow-x-auto max-h-[45vh] custom-scrollbar">
                                <table class="w-full text-center">
                                    <thead class="bg-slate-50 border-b border-slate-100 italic">
                                        <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            <th class="px-6 py-5 text-left pl-10 border-r border-slate-100">Producto
                                            </th>
                                            <th class="px-6 py-5 border-r border-slate-100 font-bold">Variante</th>
                                            <th class="px-6 py-5 border-r border-slate-100">Peso Estimado</th>
                                            <th class="px-6 py-5">Sugerencia de Carga</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaConfirmacionBody" class="divide-y divide-slate-50"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="mensajesGlobales"
                            class="px-4 py-3 bg-rose-50 border border-rose-100 rounded-2xl empty:hidden text-rose-600 text-xs font-bold leading-relaxed">
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                            <p
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic flex items-center gap-2">
                                <span class="flex h-2 w-2 rounded-full bg-emerald-400"></span>
                                El sistema optimiza el número de camiones según la capacidad de carga
                            </p>

                            <div class="flex items-center gap-4">
                                <button type="button" onclick="cerrarModalConfirmacion()"
                                    class="px-6 py-4 text-sm font-black text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">
                                    Cerrar y Revisar
                                </button>
                                <button type="submit"
                                    class="bg-slate-900 hover:bg-black text-white px-10 py-5 rounded-[1.5rem] text-sm font-black shadow-2xl shadow-slate-200 transition-all transform active:scale-95 flex items-center gap-3">
                                    Finalizar y Emitir Pedido
                                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
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

        // Mostrar modal de confirmación basado en el carrito de Alpine.js
        function mostrarConfirmacion(cartData) {
            const cart = cartData || [];
            if (cart.length === 0) {
                Swal.fire('Atención', 'El carrito está vacío', 'info');
                return;
            }

            const tbody = document.getElementById('tablaConfirmacionBody');
            tbody.innerHTML = '';

            cart.forEach((item) => {
                const clave = item.id;
                const tipo = item.tipo;
                const diametro = item.diametro;
                const cantidad = parseFloat(item.cantidad);
                const longitud = item.longitud || null;
                const productoBaseId = item.producto_base_id;

                const fila = document.createElement('tr');
                fila.className = "bg-white border-b border-slate-200 transition-colors hover:bg-slate-50";

                fila.innerHTML = `
                <td class="px-4 py-4 text-sm font-bold text-slate-700 text-left border-r border-slate-100 italic">
                    ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}
                </td>
                <td class="px-4 py-4 text-sm font-black text-slate-500 border-r border-slate-100">
                    Ø${diametro}mm${longitud ? ` / ${longitud}m` : ''}
                </td>
                <td class="px-4 py-4 border-r border-slate-100">
                    <div class="relative">
                        <input type="number" class="peso-total w-full px-4 py-2 border border-slate-200 rounded-xl font-black text-slate-800 focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition-all"
                               name="detalles[${clave}][cantidad]" value="${cantidad}" step="100" min="0">
                        <span class="absolute right-3 top-2.5 text-[10px] font-black text-slate-400">kg</span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <div class="fechas-camion flex flex-col gap-3 w-full" id="fechas-camion-${clave}"></div>
                </td>
                <input type="hidden" name="seleccionados[]" value="${clave}">
                <input type="hidden" name="detalles[${clave}][producto_base_id]" value="${productoBaseId}">
                <input type="hidden" name="detalles[${clave}][tipo]" value="${tipo}">
                <input type="hidden" name="detalles[${clave}][diametro]" value="${diametro}">
                ${longitud ? `<input type="hidden" name="detalles[${clave}][longitud]" value="${longitud}">` : ''}
            `;
                tbody.appendChild(fila);

                const inputPeso = fila.querySelector('.peso-total');
                generarFechasPorPeso(inputPeso, clave);
            });

            dispararSugerirMultiple();
            const modal = document.getElementById('modalConfirmacion');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Generar inputs de fecha según el peso
        function generarFechasPorPeso(input, clave) {
            const peso = parseFloat(input.value || 0);
            const contenedorFechas = document.getElementById(`fechas-camion-${clave}`);
            if (!contenedorFechas) return;

            contenedorFechas.innerHTML = '';

            const bloques = Math.ceil(peso / 25000);
            for (let i = 0; i < bloques; i++) {
                const pesoBloque = Math.min(25000, peso - i * 25000);

                const lineaCamion = document.createElement('div');
                lineaCamion.className = 'flex items-center gap-2 p-2 bg-white rounded border border-gray-200';
                lineaCamion.id = `linea-camion-${clave}-${i}`;

                lineaCamion.innerHTML = `
                <div class="flex flex-col gap-1.5 flex-1">
                    <label class="text-[10px] text-slate-500 font-black uppercase tracking-widest leading-none">Camión ${i + 1} - ${pesoBloque.toLocaleString('es-ES')} kg</label>
                    <input type="date" 
                           name="productos[${clave}][${i + 1}][fecha]" 
                           required 
                           class="border border-slate-200 px-3 py-2 rounded-xl text-sm font-bold w-full focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition-all outline-none">
                    <input type="hidden" 
                           name="productos[${clave}][${i + 1}][peso]" 
                           value="${pesoBloque}">
                </div>
                <div class="flex-1">
                    <div class="pg-asignacion-${clave}-${i} text-[10px] p-3 bg-slate-50 rounded-xl border border-slate-100 min-h-[60px] flex items-center justify-center font-bold text-slate-400">
                        Selecciona fabricante/distribuidor
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
                if (loadingIndicator) {
                    loadingIndicator.classList.remove('hidden');
                    loadingIndicator.classList.add('flex');
                }
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
                            contenedorStock.style.opacity = '1';

                            // Reinicializar Alpine.js en el nuevo contenido inyectado
                            if (typeof Alpine !== 'undefined') {
                                Alpine.initTree(contenedorStock);
                            }
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
                        if (loadingIndicator) {
                            loadingIndicator.classList.remove('flex');
                            loadingIndicator.classList.add('hidden');
                        }
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
