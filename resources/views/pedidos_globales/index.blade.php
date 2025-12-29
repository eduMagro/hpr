<x-app-layout>
    <x-slot name="title">Pedidos Globales - {{ config('app.name') }}</x-slot>
    <div class="px-4 py-6 max-w-[1800px] mx-auto">

        {{-- Botón Crear Pedido Global --}}
        <button onclick="abrirModalPedidoGlobal()"
            class="group mb-6 px-5 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:shadow-emerald-200/50 hover:shadow-md transition-all duration-300 flex items-center gap-2">
            <svg class="w-5 h-5 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            Crear Pedido Global
        </button>

        @livewire('pedidos-globales-table')
    </div>

    {{-- =========================
         MODAL CREAR PEDIDO GLOBAL
       ========================= --}}
    <div id="modalPedidoGlobal"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform transition-all">
            {{-- Header del Modal --}}
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Nuevo Pedido Global</h3>
                            <p class="text-emerald-100 text-xs">Crear contrato de suministro</p>
                        </div>
                    </div>
                    <button onclick="cerrarModalPedidoGlobal()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Contenido del Modal --}}
            <form id="formPedidoGlobal" class="p-6 space-y-5">
                @csrf

                {{-- Cantidad Total --}}
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Cantidad Total (kg)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </span>
                        <input type="number" name="cantidad_total" step="5000" required
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                            placeholder="Ej: 500000">
                    </div>
                </div>

                {{-- Fabricante / Distribuidor --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Fabricante
                        </label>
                        <select name="fabricante_id"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                            <option value="">Seleccionar...</option>
                            @foreach (\App\Models\Fabricante::orderBy('nombre')->get() as $fabricante)
                                <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Distribuidor
                        </label>
                        <select name="distribuidor_id"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                            <option value="">Seleccionar...</option>
                            @foreach (\App\Models\Distribuidor::orderBy('nombre')->get() as $distribuidor)
                                <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Precio de Referencia --}}
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Precio de Referencia (€/kg)
                        <span class="text-slate-400 font-medium normal-case">(Opcional)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <input type="number" name="precio_referencia" step="0.01" min="0"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                            placeholder="Ej: 0.64">
                    </div>
                </div>

                {{-- Botones de acción --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalPedidoGlobal()"
                        class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 rounded-xl transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Crear Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================
         SCRIPTS
       ========================= --}}
    <script>
        function initPedidosGlobalesIndex() {
            const modal = document.getElementById('modalPedidoGlobal');
            const form = document.getElementById('formPedidoGlobal');

            if (!form || form.dataset.initialized) return;
            form.dataset.initialized = 'true';

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creando...
                `;

                fetch("{{ route('pedidos_globales.store') }}", {
                        method: "POST",
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(async res => {
                        const contentType = res.headers.get("content-type") || '';
                        if (res.ok && contentType.includes("application/json")) {
                            const data = await res.json();

                            // Cerrar modal
                            cerrarModalPedidoGlobal();

                            // Resetear formulario
                            form.reset();

                            // Refrescar tabla Livewire dinámicamente
                            Livewire.dispatch('pedidoGlobalCreado');

                            // Toast pequeño arriba
                            Swal.fire({
                                icon: 'success',
                                title: 'Pedido creado',
                                text: data.codigo ? `Código: ${data.codigo}` :
                                    'Pedido global creado correctamente',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                customClass: {
                                    popup: 'swal2-toast-custom'
                                }
                            });
                        } else {
                            const text = await res.text();
                            throw new Error("Error inesperado:\n" + text.slice(0, 600));
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message || 'Error creando pedido global.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000
                        });
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    });
            });
        }

        function abrirModalPedidoGlobal() {
            const modal = document.getElementById('modalPedidoGlobal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalPedidoGlobal() {
            const modal = document.getElementById('modalPedidoGlobal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalPedidoGlobal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalPedidoGlobal();
            }
        });

        // Inicialización para carga normal y navegación SPA
        document.addEventListener('DOMContentLoaded', initPedidosGlobalesIndex);
        document.addEventListener('livewire:navigated', initPedidosGlobalesIndex);
    </script>
</x-app-layout>
