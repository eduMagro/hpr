<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>

    <div class="w-full">
        <!-- Botones para desktop -->
        <div class="hidden md:flex items-center gap-2">
            {{-- Botón y modal de importar --}}
            <button type="button" id="btn-abrir-import"
                class="px-4 py-2 rounded bg-gradient-to-tr from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold shadow-sm">
                Importar planillas
            </button>

            <button type="button" id="btn-completar-planillas" data-url="{{ route('planillas.completarTodas') }}"
                class="bg-gradient-to-tr from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-2 px-4 rounded shadow-sm">
                Completar planillas
            </button>
        </div>

        <div id="modal-import" class="fixed inset-0 z-[60] hidden">
            <div id="modal-import-overlay" class="absolute inset-0 bg-black/50"></div>
            <div class="relative mx-auto mt-24 bg-white rounded-lg shadow-xl w-[95%] max-w-md p-5">
                <h3 class="text-lg font-semibold mb-3">Importar planillas
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    Selecciona el archivo y el <b>día de aprobación</b>.
                    La <b>fecha estimada de entrega</b> = <b>aprobación + 7
                        días</b>.
                </p>

                <form id="form-import-modal" method="POST" action="{{ route('planillas.crearImport') }}"
                    enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="import_id" id="import_id">

                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo
                        (.xlsx / .xls)</label>
                    <input type="file" name="file" id="file" accept=".xlsx,.xls"
                        class="w-full border rounded px-3 py-2 mb-4" required>

                    <label class="block text-sm font-medium text-gray-700 mb-1">Día
                        de aprobación</label>
                    <input type="date" name="fecha_aprobacion" id="fecha_aprobacion"
                        class="w-full border rounded px-3 py-2 mb-4" required>

                    {{-- Barra de progreso --}}
                    <div id="import-progress-wrap" class="hidden mb-3">
                        <div class="flex justify-between text-sm mb-1">
                            <span id="import-progress-text" class="text-gray-700">Progreso</span>
                            <span id="import-progress-percent" class="text-gray-500">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded h-3 overflow-hidden">
                            <div id="import-progress-bar" class="h-3 bg-blue-600 transition-all duration-200"
                                style="width:0%"></div>
                        </div>
                        <p id="import-progress-msg" class="text-xs text-gray-500 mt-1"></p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="btn-cancelar-import"
                            class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit" id="btn-confirmar-import"
                            class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">
                            Confirmar e importar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="hidden md:block">
            @livewire('planillas-table')
        </div>

        <!-- Vista móvil -->
        <div class="block md:hidden mt-4 space-y-3" x-data="{ filtrosAbiertos: false }">
            <div>
                <div class="bg-gradient-to-tr from-blue-700 to-blue-600 text-white p-3 shadow-lg cursor-pointer"
                    :class="filtrosAbiertos ? 'rounded-t-xl' : 'rounded-xl'"
                    @click="filtrosAbiertos = !filtrosAbiertos">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-1">
                            {{-- Ícono toggle filtros --}}
                            <div class="text-white">
                                <svg x-show="!filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                                <svg x-show="filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 15l7-7 7 7" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold">Planillas</h2>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click.stop
                                class="bg-white/15 text-white text-[10px] font-semibold px-2.5 py-1.5 rounded-lg shadow hover:bg-white/25 transition"
                                onclick="document.getElementById('btn-abrir-import')?.click()">
                                Importar
                            </button>
                            <button id="btn-completar-planillas-mobile" @click.stop
                                data-url="{{ route('planillas.completarTodas') }}"
                                class="bg-white/15 text-white text-[10px] font-semibold px-2.5 py-1.5 rounded-lg shadow hover:bg-white/25 transition">
                                Completar
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="filtrosAbiertos" x-collapse
                    class="bg-white border border-gray-200 rounded-b-xl shadow-sm p-3">
                    <form method="GET" action="{{ route('planillas.index') }}" class="space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Código</label>
                                <input type="text" name="codigo" value="{{ request('codigo') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Estado</label>
                                <select name="estado"
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700">
                                    <option value="">Todos</option>
                                    <option value="pendiente" @selected(request('estado') === 'pendiente')>Pendiente</option>
                                    <option value="fabricando" @selected(request('estado') === 'fabricando')>Fabricando</option>
                                    <option value="completada" @selected(request('estado') === 'completada')>Completada</option>
                                    <option value="montaje" @selected(request('estado') === 'montaje')>Montaje</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            @include('components.tabla.limpiar-filtros', [
                                'href' => route('planillas.index'),
                            ])
                            <button type="submit"
                                class="bg-gray-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow hover:bg-gray-800">
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @php
                $mobilePage = max(1, (int) request('mpage', 1));
                $perPage = 10;

                // Query con filtros
                $query = \App\Models\Planilla::with(['cliente', 'obra']);

                // Aplicar filtros
                if (request('codigo')) {
                    $query->where('codigo', 'like', '%' . request('codigo') . '%');
                }

                if (request('estado')) {
                    $query->where('estado', request('estado'));
                }

                // Obtener el total de resultados
                $totalResultados = $query->count();
                $totalPaginas = $totalResultados > 0 ? (int) ceil($totalResultados / $perPage) : 1;
                $mobilePage = min($mobilePage, $totalPaginas); // No permitir páginas mayores al total

                // Obtener planillas
                $planillasMobile = $query
                    ->latest()
                    ->skip(($mobilePage - 1) * $perPage)
                    ->take($perPage + 1) // Tomamos una más para saber si hay más páginas
                    ->get();

                $hayMasPlanillas = $planillasMobile->count() > $perPage;

                // Si hay más, removemos la última para mostrar solo 10
                if ($hayMasPlanillas) {
                    $planillasMobile = $planillasMobile->take($perPage);
                }

                // Calcular firstItem y lastItem
                $firstItem = ($mobilePage - 1) * $perPage + 1;
                $lastItem = min($mobilePage * $perPage, $totalResultados);
            @endphp

            <div class="space-y-2">
                @forelse ($planillasMobile as $planilla)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-tr from-blue-700 to-blue-600 text-white px-3 py-2 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <h3 class="text-sm font-semibold tracking-tight truncate">{{ $planilla->codigo }}</h3>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/10 border border-white/20">
                                    {{ strtoupper($planilla->estado ?? '—') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2 text-xs text-gray-700">
                            <div class="flex items-center justify-between gap-2 text-[10px]">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Peso</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ number_format($planilla->peso_total ?? 0, 0) }} kg</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Importación</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $planilla->created_at->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Entrega</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $planilla->fecha_estimada_entrega ? \Carbon\Carbon::parse($planilla->fecha_estimada_entrega)->format('d/m/Y') : '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Empresa</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $planilla->cliente->empresa ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-[10px]">
                                <p class="uppercase tracking-wide text-gray-500">Obra</p>
                                <p class="font-semibold text-gray-900 truncate">{{ $planilla->obra->obra ?? '—' }}
                                </p>
                            </div>


                            <div class="flex items-center gap-1 text-xs font-semibold justify-end">
                                <a href="{{ route('planillas.show', $planilla->id) }}"
                                    class="px-2 py-1 rounded-lg bg-gray-600 text-white hover:bg-gray-800 flex items-center">
                                    Ver
                                </a>
                                <a href="{{ route('elementos.index', ['planilla_id' => $planilla->id]) }}"
                                    class="px-2 py-1 rounded-lg bg-blue-600 text-white hover:bg-blue-700 flex items-center">
                                    Elementos
                                </a>
                                <button type="button" onclick="abrirModalReimportar({{ $planilla->id }})"
                                    class="px-2 py-1 rounded-lg bg-orange-400 text-white hover:bg-orange-500 flex items-center">
                                    Reimportar
                                </button>
                                @if (!($planilla->revisada ?? false))
                                    <form action="{{ route('planillas.marcarRevisada', $planilla->id) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="px-2 py-1 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500">
                                            Revisar
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('planillas.destroy', $planilla->id) }}" method="POST"
                                    class="flex items-center gap-1"
                                    onsubmit="return confirm('¿Eliminar esta planilla? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2 py-1 rounded-lg bg-red-600 text-red-800 hover:bg-red-300">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path
                                                    d="M3 6.38597C3 5.90152 3.34538 5.50879 3.77143 5.50879L6.43567 5.50832C6.96502 5.49306 7.43202 5.11033 7.61214 4.54412C7.61688 4.52923 7.62232 4.51087 7.64185 4.44424L7.75665 4.05256C7.8269 3.81241 7.8881 3.60318 7.97375 3.41617C8.31209 2.67736 8.93808 2.16432 9.66147 2.03297C9.84457 1.99972 10.0385 1.99986 10.2611 2.00002H13.7391C13.9617 1.99986 14.1556 1.99972 14.3387 2.03297C15.0621 2.16432 15.6881 2.67736 16.0264 3.41617C16.1121 3.60318 16.1733 3.81241 16.2435 4.05256L16.3583 4.44424C16.3778 4.51087 16.3833 4.52923 16.388 4.54412C16.5682 5.11033 17.1278 5.49353 17.6571 5.50879H20.2286C20.6546 5.50879 21 5.90152 21 6.38597C21 6.87043 20.6546 7.26316 20.2286 7.26316H3.77143C3.34538 7.26316 3 6.87043 3 6.38597Z"
                                                    fill="#ffffff"></path>
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M11.5956 22.0001H12.4044C15.1871 22.0001 16.5785 22.0001 17.4831 21.1142C18.3878 20.2283 18.4803 18.7751 18.6654 15.8686L18.9321 11.6807C19.0326 10.1037 19.0828 9.31524 18.6289 8.81558C18.1751 8.31592 17.4087 8.31592 15.876 8.31592H8.12404C6.59127 8.31592 5.82488 8.31592 5.37105 8.81558C4.91722 9.31524 4.96744 10.1037 5.06788 11.6807L5.33459 15.8686C5.5197 18.7751 5.61225 20.2283 6.51689 21.1142C7.42153 22.0001 8.81289 22.0001 11.5956 22.0001ZM10.2463 12.1886C10.2051 11.7548 9.83753 11.4382 9.42537 11.4816C9.01321 11.525 8.71251 11.9119 8.75372 12.3457L9.25372 17.6089C9.29494 18.0427 9.66247 18.3593 10.0746 18.3159C10.4868 18.2725 10.7875 17.8856 10.7463 17.4518L10.2463 12.1886ZM14.5746 11.4816C14.9868 11.525 15.2875 11.9119 15.2463 12.3457L14.7463 17.6089C14.7051 18.0427 14.3375 18.3593 13.9254 18.3159C13.5132 18.2725 13.2125 17.8856 13.2537 17.4518L13.7537 12.1886C13.7949 11.7548 14.1625 11.4382 14.5746 11.4816Z"
                                                    fill="#ffffff"></path>
                                            </g>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-600">
                        No hay planillas disponibles.
                    </div>
                @endforelse

                {{-- Paginación móvil --}}
                <x-tabla.paginacion-mobile :currentPage="$mobilePage" :totalPages="$totalPaginas" :totalResults="$totalResultados" :firstItem="$firstItem"
                    :lastItem="$lastItem" route="planillas.index" :requestParams="request()->except('mpage')" />
            </div>
        </div>

    </div>

    @push('modals')
        <!-- Modal de reimportar (compartido entre desktop y móvil) -->
        <div id="modal-reimportar" class="hidden absolute inset-0 z-[99999]">
            <!-- Overlay -->
            <div class="absolute inset-0 bg-black/50" onclick="cerrarModalReimportar()"></div>

            <!-- Modal Centrado Absolutamente -->
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-lg bg-white rounded-lg shadow-2xl p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Añade modificaciones del cliente</h2>

                <form id="form-reimportar" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="archivo-reimportar" class="block text-sm font-medium text-gray-700">
                            Selecciona el nuevo archivo:
                        </label>
                        <input type="file" name="archivo" id="archivo-reimportar" accept=".csv,.xlsx,.xls" required
                            class="mt-1 block w-full border border-gray-300 rounded p-2 text-sm">
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="cerrarModalReimportar()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded">
                            🔄 Reimportar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endpush

    <script>
        // Variable global para evitar reinicialización múltiple (persistente entre navegaciones Livewire)
        var modalInitialized = window.modalInitialized || false;
        var modalInitAttempts = 0;
        window.modalInitialized = modalInitialized;

        function initModal() {
            const btnAbrir = document.getElementById('btn-abrir-import');
            const modal = document.getElementById('modal-import');
            const overlay = document.getElementById('modal-import-overlay');
            const btnCancel = document.getElementById('btn-cancelar-import');
            const form = document.getElementById('form-import-modal');
            const inputFecha = document.getElementById('fecha_aprobacion');

            if (!btnAbrir || !modal || !overlay || !btnCancel || !form || !inputFecha) {
                console.log('⏳ Esperando elementos del modal de importación...');
                if (modalInitAttempts < 50) {
                    modalInitAttempts++;
                    setTimeout(initModal, 100);
                } else {
                    console.warn('⚠️ No se encontraron los elementos del modal tras varios intentos, deteniendo.');
                }
                return;
            }

            // Evitar reinicializar si ya está configurado
            if (modalInitialized) {
                console.log('ℹ️ Modal ya inicializado, saltando...');
                return;
            }

            console.log('✅ Modal de importación inicializado');
            modalInitialized = true;

            const wrap = document.getElementById('import-progress-wrap');
            const bar = document.getElementById('import-progress-bar');
            const pct = document.getElementById('import-progress-percent');
            const msg = document.getElementById('import-progress-msg');
            const txt = document.getElementById('import-progress-text');
            const btnSend = document.getElementById('btn-confirmar-import');
            const importIdInput = document.getElementById('import_id');

            // Función para generar UUID
            function uuidv4() {
                if (crypto?.randomUUID) return crypto.randomUUID();
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                    const r = Math.random() * 16 | 0,
                        v = c === 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            }

            // Función para abrir modal
            function abrir() {
                console.log('🔓 Abriendo modal de importación');
                modal.classList.remove('hidden');
                importIdInput.value = uuidv4();
            }

            // Función para cerrar modal
            function cerrar() {
                console.log('🔒 Cerrando modal de importación');
                modal.classList.add('hidden');
            }

            // Configurar fecha por defecto
            const hoy = new Date().toISOString().split('T')[0];
            inputFecha.value = hoy;
            inputFecha.max = hoy;

            // Event listeners
            btnAbrir.addEventListener('click', function(e) {
                console.log('🖱️ Click en botón importar');
                e.preventDefault();
                abrir();
            });

            overlay.addEventListener('click', cerrar);
            btnCancel.addEventListener('click', cerrar);

            // Persistir para evitar redeclaración en navegaciones Livewire
            var pollTimer = window.planillasPollTimer || null;
            window.planillasPollTimer = pollTimer;

            function startPolling(importId) {
                wrap.classList.remove('hidden');
                txt.textContent = 'Importando...';
                msg.textContent = 'Procesando filas del Excel...';
                const url = `/api/planillas/import/progress/${encodeURIComponent(importId)}`;
                pollTimer = setInterval(async () => {
                    try {
                        const res = await fetch(url, {
                            cache: 'no-store'
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        const percent = Math.min(100, Math.max(0, Math.round(data.percent ?? 0)));
                        bar.style.width = percent + '%';
                        pct.textContent = percent + '%';
                        msg.textContent = data.message ?? '';

                        if (data.status === 'error') {
                            clearInterval(pollTimer);
                            txt.textContent = 'Error';
                            bar.classList.remove('bg-blue-600');
                            bar.classList.add('bg-red-600');
                        }
                        if (data.status === 'done' || percent >= 100) {
                            clearInterval(pollTimer);
                            txt.textContent = 'Completado';
                            bar.style.width = '100%';
                            pct.textContent = '100%';
                            msg.textContent = 'Importación finalizada.';
                        }
                    } catch (e) {
                        console.error('Error en polling:', e);
                    }
                }, 600);
            }

            form.addEventListener('submit', function(ev) {
                const importId = importIdInput.value;

                // Mostrar barra de progreso indeterminada
                wrap.classList.remove('hidden');
                txt.textContent = 'Procesando importación...';
                msg.textContent = 'Por favor espera, esto puede tardar unos minutos.';
                bar.style.width = '50%';
                bar.classList.add('animate-pulse');
                pct.textContent = '';

                btnSend.disabled = true;
                btnCancel.disabled = true;

                // El formulario se enviará normalmente (sin preventDefault)
                // y el servidor hará redirect con session para alerts.blade.php
            });
        }

        // Modal reimportar móvil/desktop
        function abrirModalReimportar(planillaId) {
            const modal = document.getElementById('modal-reimportar');
            const form = document.getElementById('form-reimportar');
            if (!modal || !form) return;
            form.action = `/planillas/${planillaId}/reimportar`;
            modal.classList.remove('hidden');
        }

        function cerrarModalReimportar() {
            const modal = document.getElementById('modal-reimportar');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function initCompletarTodas() {
            const btnCompletar = document.getElementById('btn-completar-planillas');
            const btnCompletarMobile = document.getElementById('btn-completar-planillas-mobile');

            if (!btnCompletar && !btnCompletarMobile) {
                return;
            }

            const url = (btnCompletar || btnCompletarMobile).dataset.url;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            async function solicitarConfirmacion() {
                if (typeof Swal === 'undefined') {
                    const confirmado = confirm(
                        'Se completarán las planillas pendientes/fabricando con fecha vencida. ¿Continuar?');
                    return {
                        isConfirmed: confirmado
                    };
                }

                return await Swal.fire({
                    title: 'Completar todas las planillas',
                    text: 'Se completarán las planillas pendientes/fabricando con fecha estimada de entrega vencida.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, completar',
                    cancelButtonText: 'Cancelar',
                });
            }

            // Función para manejar el click
            async function handleClick(event) {
                event.preventDefault();

                const confirmacion = await solicitarConfirmacion();
                if (!confirmacion?.isConfirmed) {
                    return;
                }

                try {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Procesando...',
                            text: 'Completando planillas elegibles, por favor espera.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });
                    }

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf ?? '',
                        },
                        body: JSON.stringify({}),
                    });

                    let data = null;
                    let rawText = '';
                    try {
                        data = await response.clone().json();
                    } catch (parseError) {
                        try {
                            rawText = await response.text();
                        } catch (_) {
                            rawText = '';
                        }
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.close();
                    }

                    if (!data && !response.ok) {
                        throw new Error('Respuesta no válida del servidor. ' + (rawText || ''));
                    }

                    console.groupCollapsed('📦 Completar todas las planillas');
                    console.log('➡️ Estado HTTP:', response.status);
                    console.log('📄 Payload bruto:', rawText);
                    console.log('📦 Payload JSON:', data);
                    console.groupEnd();

                    const detalles = data?.detalles ?? data ?? {};
                    const procesadasOk = Number(detalles.procesadas_ok ?? 0);
                    const omitidasFecha = Number(detalles.omitidas_fecha ?? 0);
                    const fallidas = Number(detalles.fallidas ?? 0);
                    const errores = Array.isArray(detalles.errores) ? detalles.errores : [];
                    const hayErrores = fallidas > 0 || errores.length > 0 || response.status >= 400;
                    const mensaje = data?.message ?? (hayErrores ?
                        'Proceso completado con incidencias.' :
                        'Proceso finalizado correctamente.');

                    let erroresHtml = '';
                    if (errores.length > 0) {
                        const maxMostrar = 5;
                        const items = errores.slice(0, maxMostrar)
                            .map((err, idx) => {
                                const planillaTxt = err.planilla_id ? `Planilla ${err.planilla_id}` :
                                    'Planilla sin ID';
                                const subTxt = err.etiqueta_sub_id ? `Sub ${err.etiqueta_sub_id}` : '';
                                return `<li class="mb-1"><strong>${idx + 1}.</strong> ${planillaTxt} ${subTxt} → ${err.error ?? 'Error desconocido'}</li>`;
                            })
                            .join('');
                        const resto = errores.length > maxMostrar ?
                            `<p class="text-xs text-gray-500 mt-1">... y ${errores.length - maxMostrar} errores adicionales.</p>` :
                            '';
                        erroresHtml = `
                                <div class="text-left mt-3">
                                    <p class="font-semibold mb-1">Detalles de errores:</p>
                                    <ul class="list-disc ml-5 text-sm">${items}</ul>
                                    ${resto}
                                </div>
                            `;
                    }

                    const resumenHtml = `
                            <p>Procesadas OK: <strong>${procesadasOk}</strong></p>
                            <p>Omitidas por fecha: <strong>${omitidasFecha}</strong></p>
                            <p>Fallidas: <strong>${fallidas}</strong></p>
                            <p class="mt-2">${mensaje}</p>
                            ${erroresHtml}
                        `;
                    const iconoFinal = hayErrores ? 'warning' : 'success';
                    const tituloFinal = hayErrores ? 'Proceso completado con incidencias' :
                        'Proceso finalizado';

                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            title: tituloFinal,
                            html: resumenHtml,
                            icon: iconoFinal,
                            confirmButtonText: 'Recargar',
                        });
                    } else {
                        alert(
                            `Procesadas OK: ${procesadasOk}\nOmitidas por fecha: ${omitidasFecha}\nFallidas: ${fallidas}`
                        );
                    }

                    window.location.reload();
                } catch (error) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            title: 'Error',
                            text: error?.message ?? 'Ocurrió un error al completar las planillas.',
                            icon: 'error',
                            confirmButtonText: 'Cerrar',
                        });
                    } else {
                        alert(error?.message ?? 'Ocurrió un error al completar las planillas.');
                    }
                }
            }

            // Agregar event listeners a ambos botones
            if (btnCompletar) {
                btnCompletar.addEventListener('click', handleClick);
            }
            if (btnCompletarMobile) {
                btnCompletarMobile.addEventListener('click', handleClick);
            }
        }

        // Inicializar al cargar el DOM
        document.addEventListener('DOMContentLoaded', function() {
            initModal();
            initCompletarTodas();
        });

        // Reinicializar después de actualizaciones de Livewire
        document.addEventListener('livewire:navigated', function() {
            console.log('🔄 Livewire navegó, reinicializando...');
            modalInitialized = false;
            initModal();
            initCompletarTodas();
        });

        // Para Livewire v2 (si es el caso)
        document.addEventListener('livewire:load', function() {
            console.log('🔄 Livewire cargado, inicializando...');
            initModal();
            initCompletarTodas();
        });
    </script>
</x-app-layout>
