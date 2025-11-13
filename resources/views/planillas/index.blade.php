<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>

    {{-- Modal de importar planillas (fuera del componente Livewire) --}}
    <div id="modal-import" class="fixed inset-0 z-[60] hidden">
        <div id="modal-import-overlay" class="absolute inset-0 bg-black/50"></div>
        <div class="relative mx-auto mt-24 bg-white rounded-lg shadow-xl w-[95%] max-w-md p-5">
            <h3 class="text-lg font-semibold mb-3">Importar planillas</h3>
            <p class="text-sm text-gray-600 mb-4">
                Selecciona el archivo y el <b>día de aprobación</b>.
                La <b>fecha estimada de entrega</b> = <b>aprobación + 7 días</b>.
            </p>

            <form id="form-import-modal" method="POST" action="{{ route('planillas.crearImport') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_id" id="import_id">

                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo (.xlsx / .xls)</label>
                <input type="file" name="file" id="file" accept=".xlsx,.xls"
                    class="w-full border rounded px-3 py-2 mb-4" required>

                <label class="block text-sm font-medium text-gray-700 mb-1">Día de aprobación</label>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que Livewire cargue
            function initModal() {
                const btnAbrir = document.getElementById('btn-abrir-import');
                const modal = document.getElementById('modal-import');
                const overlay = document.getElementById('modal-import-overlay');
                const btnCancel = document.getElementById('btn-cancelar-import');
                const form = document.getElementById('form-import-modal');
                const inputFecha = document.getElementById('fecha_aprobacion');

                if (!btnAbrir) {
                    // Si el botón no existe aún, reintentar después de un delay
                    setTimeout(initModal, 100);
                    return;
                }

            const wrap = document.getElementById('import-progress-wrap');
            const bar = document.getElementById('import-progress-bar');
            const pct = document.getElementById('import-progress-percent');
            const msg = document.getElementById('import-progress-msg');
            const txt = document.getElementById('import-progress-text');
            const btnSend = document.getElementById('btn-confirmar-import');
            const importIdInput = document.getElementById('import_id');

            function abrir() {
                modal.classList.remove('hidden');
            }

            function cerrar() {
                modal.classList.add('hidden');
            }

            const hoy = new Date().toISOString().split('T')[0];
            inputFecha.value = hoy;
            inputFecha.max = hoy;

            if (btnAbrir) {
                btnAbrir.addEventListener('click', abrir);
            }
            overlay.addEventListener('click', cerrar);
            btnCancel.addEventListener('click', cerrar);

            function uuidv4() {
                if (crypto?.randomUUID) return crypto.randomUUID();
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,
                    c => {
                        const r = Math.random() * 16 | 0,
                            v = c === 'x' ? r : (r & 0x3 | 0x8);
                        return v.toString(16);
                    });
            }

            let pollTimer = null;

            function startPolling(importId) {
                wrap.classList.remove('hidden');
                txt.textContent = 'Importando...';
                msg.textContent = 'Procesando filas del Excel...';
                const url = `/api/planillas/import/progress/${encodeURIComponent(importId)}`;
                pollTimer = setInterval(async () => {
                    try {
                        const res = await fetch(url, { cache: 'no-store' });
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
                            btnSend.disabled = false;
                        }
                        if (data.status === 'done' || percent >= 100) {
                            clearInterval(pollTimer);
                            txt.textContent = 'Completado';
                            bar.style.width = '100%';
                            pct.textContent = '100%';
                            msg.textContent = 'Importación finalizada.';

                            // Cerrar el modal de importación
                            setTimeout(() => {
                                cerrar();

                                // Mostrar SweetAlert con el resultado
                                const advertencias = data.advertencias || [];
                                const resultado = data.resultado || {};
                                const mensajeCompleto = data.mensaje_completo || '';

                                // Convertir saltos de línea a <br>
                                const mensajeHtml = mensajeCompleto.replace(/\n/g, '<br>');

                                const config = {
                                    icon: advertencias.length > 0 ? 'warning' : 'success',
                                    title: advertencias.length > 0 ? 'Importación completada con advertencias' : 'Importación completada',
                                    html: '<div style="text-align: left; font-family: monospace; white-space: pre-wrap;">' +
                                        mensajeHtml + '</div>',
                                    confirmButtonColor: '#28a745',
                                    width: '650px',
                                };

                                // Si tiene advertencias, añadir botón de reportar
                                if (advertencias.length > 0) {
                                    config.showCancelButton = true;
                                    config.cancelButtonText = '⚠️ Reportar Advertencias';
                                    config.confirmButtonText = 'Aceptar';
                                    config.cancelButtonColor = '#f59e0b';
                                }

                                if (typeof Swal !== 'undefined') {
                                    Swal.fire(config).then((result) => {
                                        // Si clickeó en "Reportar Advertencias"
                                        if (result.dismiss === Swal.DismissReason.cancel && advertencias.length > 0) {
                                            const asunto = 'Advertencias en importación de planillas';
                                            notificarProgramador(mensajeCompleto, asunto);
                                        }
                                        // Recargar la página después de cerrar el modal
                                        window.location.reload();
                                    });
                                } else {
                                    window.location.reload();
                                }
                            }, 500);
                        }
                    } catch (e) {}
                }, 600);
            }

            form.addEventListener('submit', async function(ev) {
                ev.preventDefault();
                const importId = uuidv4();
                importIdInput.value = importId;
                startPolling(importId);
                btnSend.disabled = true;

                const fd = new FormData(form);
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    if (!res.ok) {
                        let errMsg = 'No se pudo iniciar/terminar la importación.';
                        try {
                            const j = await res.json();
                            if (j?.message) errMsg = j.message;
                        } catch {}
                        clearInterval(pollTimer);
                        wrap.classList.remove('hidden');
                        txt.textContent = 'Error';
                        bar.classList.remove('bg-blue-600');
                        bar.classList.add('bg-red-600');
                        msg.textContent = errMsg;
                        btnSend.disabled = false;
                    }
                } catch (e) {
                    clearInterval(pollTimer);
                    wrap.classList.remove('hidden');
                    txt.textContent = 'Error';
                    bar.classList.remove('bg-blue-600');
                    bar.classList.add('bg-red-600');
                    msg.textContent = 'Fallo de red durante la importación.';
                    btnSend.disabled = false;
                }
            });
            }

            // Iniciar el modal
            initModal();
        });
    </script>

    <style>
        .cursor-espera * {
            cursor: wait !important;
            pointer-events: none !important;
        }
    </style>

    @livewire('planillas-table')
</x-app-layout>
