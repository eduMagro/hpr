<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>

    <div class="w-full px-6 py-4">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-4">

            {{-- Botón y modal de importar --}}
            <button type="button" id="btn-abrir-import"
                class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold">
                Importar planillas
            </button>

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

            <form action="{{ route('planillas.completarTodas') }}" method="POST"
                onsubmit="return confirm('¿Completar todas las planillas pendientes?');">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Completar todas las planillas
                </button>
            </form>

        </div>

        @livewire('planillas-table')

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initModal() {
                const btnAbrir = document.getElementById('btn-abrir-import');
                const modal = document.getElementById('modal-import');
                const overlay = document.getElementById('modal-import-overlay');
                const btnCancel = document.getElementById('btn-cancelar-import');
                const form = document.getElementById('form-import-modal');
                const inputFecha = document.getElementById('fecha_aprobacion');

                if (!btnAbrir || !modal || !overlay || !btnCancel || !form || !inputFecha) {
                    console.log('⏳ Esperando elementos del modal de importación...');
                    setTimeout(initModal, 100);
                    return;
                }

                console.log('✅ Modal de importación inicializado');

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

                let pollTimer = null;

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

            // Iniciar
            initModal();
        });
    </script>
</x-app-layout>
