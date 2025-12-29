<x-app-layout>
    <x-slot name="title">Alianzas Estratégicas - {{ config('app.name') }}</x-slot>

    <style>
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hover-lift {
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        [contenteditable]:focus {
            outline: none;
            background: rgba(79, 70, 229, 0.05);
            border-radius: 4px;
        }
    </style>

    <div class="px-8 py-8" x-data="{ openFabricanteModal: false, openDistribuidorModal: false }">

        <!-- Header Section -->
        <div class="max-w-7xl mx-auto mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 text-indigo-600">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                        <i data-lucide="handshake" class="w-6 h-6"></i>
                    </div>
                    <span class="text-sm font-bold uppercase tracking-[0.2em]">Ecosistema</span>
                </div>
                <h1 class="text-4xl font-extrabold text-slate-900 font-outfit tracking-tight">Fabricantes y <span
                        class="text-indigo-600">Distribuidores</span></h1>
                <p class="text-slate-500 mt-2 font-medium">Gestiona tu red de socios industriales y de suministro de
                    forma centralizada.</p>
            </div>

            <div class="flex items-center gap-4 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                <button @click="openFabricanteModal = true"
                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold flex items-center gap-2 transition-all shadow-md shadow-indigo-100 active:scale-95 group">
                    <i data-lucide="factory" class="w-5 h-5"></i>
                    Nuevo Fabricante
                </button>
                <button @click="openDistribuidorModal = true"
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold flex items-center gap-2 transition-all shadow-md shadow-emerald-100 active:scale-95 group">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                    Nuevo Distribuidor
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto space-y-16">

            <!-- FABRICANTES SECTION -->
            <section>
                <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-indigo-600 shadow-sm">
                            <i data-lucide="factory" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 font-outfit">Fabricantes</h2>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-0.5">Socios de
                                Producción</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-50 rounded-full text-xs font-bold text-indigo-600">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span id="fabricantes-count">{{ $fabricantes->count() }} Fabricantes</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="fabricantes-grid">
                    @foreach ($fabricantes as $fabricante)
                        @include('fabricantes.partials.fabricante-card', ['fabricante' => $fabricante])
                    @endforeach

                    <!-- Add Placeholder -->
                    <div @click="openFabricanteModal = true"
                        class="border-2 border-dashed border-slate-200 rounded-[2.5rem] p-8 flex flex-col items-center justify-center gap-4 text-slate-400 hover:border-indigo-300 hover:text-indigo-500 transition-all cursor-pointer group">
                        <div
                            class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center group-hover:bg-indigo-50 transition-colors">
                            <i data-lucide="plus" class="w-8 h-8"></i>
                        </div>
                        <span class="font-bold text-sm tracking-widest uppercase">Añadir Fabricante</span>
                    </div>
                </div>
            </section>

            <!-- DISTRIBUIDORES SECTION -->
            <section>
                <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-emerald-600 shadow-sm">
                            <i data-lucide="truck" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 font-outfit">Distribuidores</h2>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-0.5">Socios de
                                Logística</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2 px-4 py-2 bg-emerald-50 rounded-full text-xs font-bold text-emerald-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span id="distribuidores-count">{{ $distribuidores->count() }} Distribuidores</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="distribuidores-grid">
                    @foreach ($distribuidores as $distribuidor)
                        @include('fabricantes.partials.distribuidor-card', [
                            'distribuidor' => $distribuidor,
                        ])
                    @endforeach

                    <!-- Add Placeholder -->
                    <div @click="openDistribuidorModal = true"
                        class="border-2 border-dashed border-slate-200 rounded-[2.5rem] p-8 flex flex-col items-center justify-center gap-4 text-slate-400 hover:border-emerald-300 hover:text-emerald-500 transition-all cursor-pointer group">
                        <div
                            class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center group-hover:bg-emerald-50 transition-colors">
                            <i data-lucide="plus" class="w-8 h-8"></i>
                        </div>
                        <span class="font-bold text-sm tracking-widest uppercase">Añadir Distribuidor</span>
                    </div>
                </div>
            </section>

        </div>

        <!-- Modal Fabricante -->
        <div x-show="openFabricanteModal" @close-fabricante-modal.window="openFabricanteModal = false" x-transition
            x-cloak class="fixed inset-0 flex items-center justify-center z-[60] bg-slate-900/80 backdrop-blur-sm p-4">
            <div @click.away="openFabricanteModal = false"
                class="bg-white p-8 rounded-[2.5rem] w-full max-w-md overflow-hidden relative shadow-2xl">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <i data-lucide="factory" class="w-24 h-24 text-indigo-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-6 font-outfit">Nuevo Fabricante</h2>
                <form action="{{ route('fabricantes.store') }}" method="POST" id="form-nuevo-fabricante"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Nombre
                            Comercial</label>
                        <input type="text" name="nombre"
                            class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-indigo-500 transition-all"
                            placeholder="Ej: Aceros Industriales S.A." required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">NIF / CIF</label>
                        <input type="text" name="nif"
                            class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-indigo-500 transition-all"
                            placeholder="Ej: B12345678" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Teléfono</label>
                            <input type="text" name="telefono"
                                class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-indigo-500 transition-all"
                                placeholder="912 345 678" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Email</label>
                            <input type="email" name="email"
                                class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-indigo-500 transition-all"
                                placeholder="contacto@empresa.com" required>
                        </div>
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="openFabricanteModal = false"
                            class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-4 rounded-2xl transition-all">Cancelar</button>
                        <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl shadow-md shadow-indigo-100 transition-all">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Distribuidor -->
        <div x-show="openDistribuidorModal" @close-distribuidor-modal.window="openDistribuidorModal = false"
            x-transition x-cloak
            class="fixed inset-0 flex items-center justify-center z-[60] bg-slate-900/80 backdrop-blur-sm p-4">
            <div @click.away="openDistribuidorModal = false"
                class="bg-white p-8 rounded-[2.5rem] w-full max-w-md overflow-hidden relative shadow-2xl">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <i data-lucide="truck" class="w-24 h-24 text-emerald-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-6 font-outfit">Nuevo Distribuidor</h2>
                <form action="{{ route('distribuidores.store') }}" method="POST" id="form-nuevo-distribuidor"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Nombre
                            Comercial</label>
                        <input type="text" name="nombre"
                            class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-emerald-500 transition-all"
                            placeholder="Ej: Logística Express" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">NIF / CIF</label>
                        <input type="text" name="nif"
                            class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-emerald-500 transition-all"
                            placeholder="Ej: B98765432" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Teléfono</label>
                            <input type="text" name="telefono"
                                class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-emerald-500 transition-all"
                                placeholder="934 567 890" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Email</label>
                            <input type="email" name="email"
                                class="w-full bg-slate-50 border-0 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-emerald-500 transition-all"
                                placeholder="ops@distribuidor.es" required>
                        </div>
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="openDistribuidorModal = false"
                            class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-4 rounded-2xl transition-all">Cancelar</button>
                        <button type="submit"
                            class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-2xl shadow-md shadow-emerald-100 transition-all">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function initFabricantesPage() {
            if (document.body.dataset.fabricantesPageInit === 'true') return;
            console.log('🚀 Inicializando Fabricantes y Distribuidores...');

            if (window.lucide) {
                lucide.createIcons();
            }

            // Delegación para edición en línea (Fabricantes y Distribuidores)
            if (!document.body.dataset.fabricantesDelegated) {
                document.addEventListener('keydown', (e) => {
                    const el = e.target.closest('.editable');
                    if (el && e.key === 'Enter') {
                        e.preventDefault();
                        el.blur();
                    }
                });

                document.addEventListener('blur', (e) => {
                    const el = e.target.closest('.editable');
                    if (!el) return;

                    const id = el.dataset.id;
                    const type = el.dataset.type; // fabricante o distribuidor
                    const field = el.dataset.field;
                    let value = el.textContent.trim();

                    if (!value) {
                        console.warn(`El campo ${field} no puede estar vacío.`);
                        return;
                    }

                    const url = type === 'fabricante' ? `/fabricantes/${id}` : `/distribuidores/${id}`;

                    fetch(url, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                [field]: value,
                                nombre: el.closest('.glass-card').querySelector('[data-field="nombre"]')
                                    .textContent.trim()
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Actualizado correctamente'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: data.message || 'Error al actualizar.'
                                });
                            }
                        })
                        .catch(err => console.error('Error:', err));
                }, true);

                document.body.dataset.fabricantesDelegated = 'true';
            }

            // AJAX para creación de Fabricante
            const formFabricante = document.getElementById('form-nuevo-fabricante');
            if (formFabricante && !formFabricante.dataset.ajaxAttached) {
                formFabricante.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.dispatchEvent(new CustomEvent('close-fabricante-modal'));

                                Swal.fire({
                                    icon: 'success',
                                    title: data.message,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    toast: true,
                                    position: 'top-end'
                                });

                                const countEl = document.getElementById('fabricantes-count');
                                if (countEl) countEl.textContent = `${data.count} Fabricantes Activos`;

                                const grid = document.getElementById('fabricantes-grid');
                                const placeholder = grid.lastElementChild;
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = data.html;
                                grid.insertBefore(tempDiv.firstElementChild, placeholder);

                                formFabricante.reset();
                                if (window.lucide) lucide.createIcons();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: data.message || 'Error al guardar.'
                                });
                            }
                        })
                        .catch(err => console.error('Error:', err));
                });
                formFabricante.dataset.ajaxAttached = 'true';
            }

            // AJAX para creación de Distribuidor
            const formDistribuidor = document.getElementById('form-nuevo-distribuidor');
            if (formDistribuidor && !formDistribuidor.dataset.ajaxAttached) {
                formDistribuidor.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.dispatchEvent(new CustomEvent('close-distribuidor-modal'));

                                Swal.fire({
                                    icon: 'success',
                                    title: data.message,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    toast: true,
                                    position: 'top-end'
                                });

                                const countEl = document.getElementById('distribuidores-count');
                                if (countEl) countEl.textContent = `${data.count} Distribuidores Activos`;

                                const grid = document.getElementById('distribuidores-grid');
                                const placeholder = grid.lastElementChild;
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = data.html;
                                grid.insertBefore(tempDiv.firstElementChild, placeholder);

                                formDistribuidor.reset();
                                if (window.lucide) lucide.createIcons();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: data.message || 'Error al guardar.'
                                });
                            }
                        })
                        .catch(err => console.error('Error:', err));
                });
                formDistribuidor.dataset.ajaxAttached = 'true';
            }

            document.body.dataset.fabricantesPageInit = 'true';

            const cleanup = () => {
                document.body.dataset.fabricantesPageInit = 'false';
            };
            document.addEventListener('livewire:navigating', cleanup, {
                once: true
            });
        }

        // Registrar e inicializar
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initFabricantesPage);
        document.addEventListener('livewire:navigated', initFabricantesPage);
        document.addEventListener('DOMContentLoaded', initFabricantesPage);
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            initFabricantesPage();
        }
    </script>
</x-app-layout>
