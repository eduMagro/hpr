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

            <button type="button" id="btn-completar-planillas" data-url="{{ route('planillas.completarTodas') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Completar todas las planillas
            </button>

        </div>

        @livewire('planillas-table')

    </div>

    <script>
        // Variable global para evitar reinicialización múltiple
        let modalInitialized = false;

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

        function initCompletarTodas() {
            const btnCompletar = document.getElementById('btn-completar-planillas');
            if (!btnCompletar) {
                return;
            }

            const url = btnCompletar.dataset.url;
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

            btnCompletar.addEventListener('click', async (event) => {
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
                            `Procesadas OK: ${procesadasOk}\nOmitidas por fecha: ${omitidasFecha}\nFallidas: ${fallidas}`);
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
            });
        }

        // Iniciar
        initModal();
        initCompletarTodas();
        });

        // Para Livewire v2 (si es el caso)
        document.addEventListener('livewire:load', function() {
            console.log('🔄 Livewire cargado, inicializando modal...');
            initModal();
            initCompletarTodas();
        });
    </script>
</x-app-layout>
