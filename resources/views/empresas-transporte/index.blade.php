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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($empresasTransporte as $empresa)
                <div x-data="{ openCamionModal: false }"
                    class="glass-card rounded-[2.5rem] overflow-hidden hover-lift group flex flex-col h-full">
                    <div class="p-8 flex-grow">
                        <!-- Card Top -->
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-xl font-bold font-outfit shadow-lg shadow-indigo-200">
                                    {{ strtoupper(substr($empresa->nombre, 0, 2)) }}
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors font-outfit editable"
                                        contenteditable="true" data-id="{{ $empresa->id }}" data-field="nombre">
                                        {{ $empresa->nombre }}
                                    </h2>
                                    <p class="text-slate-400 text-xs flex items-center gap-1 mt-1">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span class="editable" contenteditable="true" data-id="{{ $empresa->id }}"
                                            data-field="direccion">
                                            {{ $empresa->direccion }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Quick Info -->
                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-3 text-slate-600 group/item">
                                <div
                                    class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center group-hover/item:bg-indigo-50 transition-colors">
                                    <i data-lucide="phone" class="w-4 h-4"></i>
                                </div>
                                <span class="text-sm font-medium editable" contenteditable="true"
                                    data-id="{{ $empresa->id }}" data-field="telefono">
                                    {{ $empresa->telefono }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-slate-600 group/item">
                                <div
                                    class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center group-hover/item:bg-indigo-50 transition-colors">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                </div>
                                <span class="text-sm font-medium editable" contenteditable="true"
                                    data-id="{{ $empresa->id }}" data-field="email">
                                    {{ $empresa->email }}
                                </span>
                            </div>
                        </div>

                        <!-- Fleet List -->
                        <div class="bg-slate-50/50 rounded-3xl p-5 border border-slate-100 mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Listado de
                                    Flota</span>
                                <span
                                    class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">{{ $empresa->camiones->count() }}
                                    Camiones</span>
                            </div>

                            @if ($empresa->camiones->isEmpty())
                                <p class="text-xs text-slate-400 italic text-center py-2">No hay camiones registrados
                                </p>
                            @else
                                <div class="space-y-3 overflow-y-auto max-h-72 custom-scrollbar pr-2">
                                    @foreach ($empresa->camiones as $camion)
                                        <div
                                            class="flex items-center justify-between p-3 bg-white rounded-2xl border border-slate-100 shadow-sm transition-all hover:border-indigo-200">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                                    <i data-lucide="truck" class="w-4 h-4"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-700 editable"
                                                        contenteditable="true" data-id="{{ $camion->id }}"
                                                        data-field="modelo">
                                                        {{ $camion->modelo }}
                                                    </p>
                                                    <p class="text-[10px] text-slate-400 font-medium">
                                                        Carga: <span class="editable" contenteditable="true"
                                                            data-id="{{ $camion->id }}"
                                                            data-field="capacidad">{{ $camion->capacidad }}</span> kg
                                                    </p>
                                                </div>
                                            </div>
                                            <span
                                                class="status-badge status-{{ strtolower($camion->estado) }} editable"
                                                contenteditable="true" data-id="{{ $camion->id }}"
                                                data-field="estado">
                                                {{ $camion->estado }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-8 pt-0 flex gap-3">
                        <button @click="openCamionModal = true"
                            class="flex-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 py-3 rounded-2xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Añadir Camión
                        </button>
                    </div>

                    <!-- Modal para añadir camión -->
                    <div x-show="openCamionModal" x-transition x-cloak
                        class="fixed inset-0 flex items-center justify-center z-[60] bg-slate-900/80 backdrop-blur-sm p-4">
                        <div @click.away="openCamionModal = false"
                            class="glass-card p-8 rounded-[2.5rem] w-full max-w-md">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 font-outfit">Añadir Camión</h2>
                            <form action="{{ route('camiones.store') }}" method="POST" class="space-y-5">
                                @csrf
                                <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Modelo /
                                        Matrícula</label>
                                    <input type="text" name="modelo" placeholder="Ej: Mercedes Actros - 1234 ABC"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                        required>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Capacidad
                                        (kg)</label>
                                    <input type="number" name="capacidad" placeholder="Ej: 24000"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                        required>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Estado</label>
                                    <select name="estado"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none"
                                        required>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>

                                <div class="flex gap-3 pt-2">
                                    <button type="button" @click="openCamionModal = false"
                                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-4 rounded-2xl font-bold transition-all">Cancelar</button>
                                    <button type="submit"
                                        class="flex-1 bg-slate-900 hover:bg-black text-white py-4 rounded-2xl font-bold shadow-lg transition-all">Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
        <div x-show="openEmpresaModal" x-transition x-cloak
            class="fixed inset-0 flex items-center justify-center z-[70] bg-slate-900/80 backdrop-blur-sm p-4">
            <div @click.away="openEmpresaModal = false"
                class="glass-card p-8 rounded-[2.5rem] w-full max-w-md animate-fade-in">
                <h2 class="text-2xl font-bold text-slate-900 mb-6 font-outfit">Añadir Empresa</h2>
                <form action="{{ route('empresas-transporte.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Nombre</label>
                        <input type="text" name="nombre"
                            class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Dirección</label>
                        <input type="text" name="direccion"
                            class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Teléfono</label>
                            <input type="text" name="telefono"
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Email</label>
                            <input type="email" name="email"
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                required>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="openEmpresaModal = false"
                            class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-4 rounded-2xl font-bold transition-all">Cancelar</button>
                        <button type="submit"
                            class="flex-1 bg-slate-900 hover:bg-black text-white py-4 rounded-2xl font-bold shadow-lg transition-all">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function initEmpresasTransportePage() {
            if (document.body.dataset.empresasTransportePageInit === 'true') return;
            console.log('🚀 Inicializando Empresas de Transporte (Premium)...');

            if (window.lucide) {
                lucide.createIcons();
            }

            const editables = document.querySelectorAll('.editable');
            editables.forEach(el => {
                const handleKeyDown = (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        el.blur();
                    }
                };

                const handleBlur = () => {
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
                };

                el.addEventListener('keydown', handleKeyDown);
                el.addEventListener('blur', handleBlur);
            });

            document.body.dataset.empresasTransportePageInit = 'true';

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
