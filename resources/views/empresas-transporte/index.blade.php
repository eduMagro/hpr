<x-app-layout>
    <x-slot name="title">Gestión de Transporte - {{ config('app.name') }}</x-slot>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }

        .font-inter {
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }

        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .gradient-text {
            background: linear-gradient(135deg, #4F46E5 0%, #10B981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .status-badge {
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.70rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-active {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-inactive,
        .status-inactivo {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .editable:focus {
            outline: none;
            background: rgba(79, 70, 229, 0.05);
            border-radius: 4px;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="{ openEmpresaModal: false }" class="container mx-auto p-4 md:p-8 font-inter">

        <!-- Header Section -->
        <header
            class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 animate-fade-in">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-900 mb-2 font-outfit">
                    Operaciones de <span class="gradient-text">Transporte</span>
                </h1>
                <p class="text-slate-500 font-medium">Gestiona tus socios logísticos y el estado de la flota en tiempo
                    real.</p>
            </div>

            <div class="flex gap-3 w-full md:w-auto">
                <button @click="openEmpresaModal = true"
                    class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-semibold shadow-lg transition-all active:scale-95">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    Añadir Empresa
                </button>
            </div>
        </header>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 mb-10">
            <div class="glass-card p-5 md:p-6 rounded-3xl border-l-4 border-indigo-500">
                <div class="bg-indigo-100 w-10 h-10 rounded-xl flex items-center justify-center text-indigo-600 mb-3">
                    <i data-lucide="building-2" class="w-5 h-5"></i>
                </div>
                <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-1">Empresas</h3>
                <p class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                    {{ $empresasTransporte->count() }}</p>
            </div>

            <div class="glass-card p-5 md:p-6 rounded-3xl border-l-4 border-emerald-500">
                <div class="bg-emerald-100 w-10 h-10 rounded-xl flex items-center justify-center text-emerald-600 mb-3">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
                <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-1">Total Flota</h3>
                <p class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                    {{ $empresasTransporte->sum(fn($e) => $e->camiones->count()) }}
                </p>
            </div>

            <div class="glass-card p-5 md:p-6 rounded-3xl border-l-4 border-amber-500">
                <div class="bg-amber-100 w-10 h-10 rounded-xl flex items-center justify-center text-amber-600 mb-3">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                </div>
                <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-1">Capacidad</h3>
                <p class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                    {{ number_format($empresasTransporte->sum(fn($e) => $e->camiones->sum('capacidad')) / 1000, 1) }}
                    <span class="text-sm font-bold text-slate-400">tn</span>
                </p>
            </div>


        </div>

        <!-- Companies Grid -->
        <div id="empresas-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($empresasTransporte as $empresa)
                @include('empresas-transporte.partials.company-card', ['empresa' => $empresa])
            @endforeach

            <!-- Add Company Placeholder -->
            <div @click="openEmpresaModal = true"
                class="rounded-[2.5rem] border-4 border-dashed border-slate-200 flex flex-col items-center justify-center p-12 text-center group cursor-pointer hover:border-indigo-400 transition-all bg-slate-50/50 min-h-[400px]">
                <div
                    class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-all mb-4">
                    <i data-lucide="plus" class="w-10 h-10"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-400 group-hover:text-indigo-600 transition-all font-outfit">
                    Añadir Transportista</h2>
                <p class="text-slate-400 text-sm mt-2">Amplía tu red de logística</p>
            </div>
        </div>

        <!-- Modal para añadir nueva empresa -->
        <div x-show="openEmpresaModal" @close-empresa-modal.window="openEmpresaModal = false"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak
            class="fixed inset-0 flex items-center justify-center z-[70] bg-slate-900/50 backdrop-blur-sm p-4">

            <div @click.away="openEmpresaModal = false"
                class="bg-white rounded-[2.5rem] w-full max-w-xl overflow-hidden animate-fade-in border-0 shadow-2xl">
                <!-- Header with Gradient Area -->
                <div class="relative h-32 bg-gradient-to-br from-indigo-600 to-emerald-500 flex items-center px-8">
                    <div class="absolute right-0 top-0 p-8 opacity-20">
                        <i data-lucide="building-2" class="w-24 h-24 text-white rotate-12"></i>
                    </div>
                    <div class="flex items-center gap-4">
                        <div
                            class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white border border-white/30 shadow-xl">
                            <i data-lucide="plus" class="w-8 h-8"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white font-outfit">Socia Logística</h2>
                            <p class="text-indigo-50/80 text-sm font-medium">Registra una nueva empresa transportista
                            </p>
                        </div>
                    </div>
                    <button @click="openEmpresaModal = false"
                        class="absolute top-6 right-6 text-white/70 hover:text-white transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Form Body -->
                <form id="form-nueva-empresa" action="{{ route('empresas-transporte.store') }}" method="POST"
                    class="p-8 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label
                                class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1 tracking-widest">Nombre
                                de la Empresa</label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                    <i data-lucide="briefcase" class="w-5 h-5"></i>
                                </div>
                                <input type="text" name="nombre" placeholder="Ej: Transportes del Sur S.A."
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-12 pr-5 py-3.5 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-slate-700 placeholder:text-slate-300"
                                    required>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label
                                class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1 tracking-widest">Dirección
                                Principal</label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                                </div>
                                <input type="text" name="direccion" placeholder="Calle, Número, Planta, Ciudad..."
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-12 pr-5 py-3.5 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-slate-700 placeholder:text-slate-300"
                                    required>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1 tracking-widest">Teléfono
                                Directo</label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                    <i data-lucide="phone" class="w-5 h-5"></i>
                                </div>
                                <input type="text" name="telefono" placeholder="+34 000 000 000"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-12 pr-5 py-3.5 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-slate-700 placeholder:text-slate-300"
                                    required>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1 tracking-widest">Email
                                Corporativo</label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                    <i data-lucide="mail" class="w-5 h-5"></i>
                                </div>
                                <input type="email" name="email" placeholder="contacto@empresa.com"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-12 pr-5 py-3.5 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-slate-700 placeholder:text-slate-300"
                                    required>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex gap-4 pt-6 border-t border-slate-100">
                        <button type="button" @click="openEmpresaModal = false"
                            class="flex-1 bg-slate-50 hover:bg-slate-100 text-slate-500 py-4 rounded-2xl font-bold transition-all border border-slate-200 active:scale-95">
                            Descartar
                        </button>
                        <button type="submit"
                            class="flex-1 bg-slate-900 hover:bg-black text-white py-4 rounded-2xl font-bold shadow-xl shadow-slate-200 transition-all flex items-center justify-center gap-2 active:scale-95 group">
                            <i data-lucide="check-circle"
                                class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                            Dar de Alta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function initEmpresasTransportePage() {
            if (document.body.dataset.empresasTransportePageInit === 'true') return;
            console.log('🚀 Inicializando Empresas y Camiones (Premium)...');

            if (window.lucide) {
                lucide.createIcons();
            }

            // Delegación de eventos para edición en línea (evita duplicados con AJAX)
            if (!document.body.dataset.empresasTransporteDelegated) {
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
                    const field = el.dataset.field;
                    let value = el.textContent.trim();

                    if (!value && field !== 'direccion') {
                        console.warn(`El campo ${field} no puede estar vacío.`);
                        return;
                    }

                    fetch("{{ route('empresas-transporte.editarField') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id,
                                field,
                                value
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
                                    title: 'Campo actualizado'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: data.error || 'Error al actualizar.'
                                });
                            }
                        })
                        .catch(err => console.error('Error:', err));
                }, true);

                document.body.dataset.empresasTransporteDelegated = 'true';
            }

            document.body.dataset.empresasTransportePageInit = 'true';

            // Interceptor de Formulario AJAX
            const formNuevaEmpresa = document.getElementById('form-nueva-empresa');
            if (formNuevaEmpresa && !formNuevaEmpresa.dataset.ajaxAttached) {
                formNuevaEmpresa.addEventListener('submit', function(e) {
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
                                window.dispatchEvent(new CustomEvent('close-empresa-modal'));

                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: data.message
                                });

                                const grid = document.getElementById('empresas-grid');
                                const placeholder = grid.lastElementChild;
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = data.html;
                                const newCard = tempDiv.firstElementChild;
                                grid.insertBefore(newCard, placeholder);

                                formNuevaEmpresa.reset();
                                if (window.lucide) lucide.createIcons();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: data.error || 'Error al guardar.'
                                });
                            }
                        })
                        .catch(err => console.error('Error:', err));
                });
                formNuevaEmpresa.dataset.ajaxAttached = 'true';
            }

            // Interceptor de Formulario AJAX Camiones (Delegado)
            if (!document.body.dataset.camionesDelegated) {
                document.addEventListener('submit', (e) => {
                    const form = e.target.closest('.form-nuevo-camion');
                    if (!form) return;

                    e.preventDefault();
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    fetch(form.action, {
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
                                window.dispatchEvent(new CustomEvent('close-camion-modal'));

                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: data.message
                                });

                                const list = document.getElementById(`trucks-list-${data.empresa_id}`);
                                const emptyMsg = document.getElementById(`empty-trucks-${data.empresa_id}`);
                                const counter = document.getElementById(`trucks-count-${data.empresa_id}`);

                                if (list) {
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = data.html;
                                    list.appendChild(tempDiv.firstElementChild);
                                    list.classList.remove('hidden');
                                }
                                if (emptyMsg) emptyMsg.classList.add('hidden');
                                if (counter) counter.textContent = `${data.count} Camiones`;

                                form.reset();
                                if (window.lucide) lucide.createIcons();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: data.error || 'Error al guardar.'
                                });
                            }
                        })
                        .catch(err => console.error('Error:', err));
                });
                document.body.dataset.camionesDelegated = 'true';
            }

            // Cleanup function for SPA navigation
            const cleanup = () => {
                document.body.dataset.empresasTransportePageInit = 'false';
            };

            document.addEventListener('livewire:navigating', cleanup, {
                once: true
            });
        }

        // Registrar en sistema global para limpieza en layout
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initEmpresasTransportePage);

        // Listeners para carga inicial y SPA
        document.addEventListener('livewire:navigated', initEmpresasTransportePage);
        document.addEventListener('DOMContentLoaded', initEmpresasTransportePage);

        // Ejecutar inmediatamente si el script se carga después del evento
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            initEmpresasTransportePage();
        }
    </script>
</x-app-layout>
