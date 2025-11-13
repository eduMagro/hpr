<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>
    <x-menu.planillas />
    <div class="w-full px-6 py-4">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-4">

            {{-- Botón y modal: igual que antes --}}
            <button type="button" id="btn-abrir-import"
                class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold">
                Importar planillas
            </button>

            <div id="modal-import" class="fixed inset-0 z-[60] hidden">
                <div id="modal-import-overlay"
                    class="absolute inset-0 bg-black/50"></div>
                <div
                    class="relative mx-auto mt-24 bg-white rounded-lg shadow-xl w-[95%] max-w-md p-5">
                    <h3 class="text-lg font-semibold mb-3">Importar planillas
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Selecciona el archivo y el <b>día de aprobación</b>.
                        La <b>fecha estimada de entrega</b> = <b>aprobación + 7
                            días</b>.
                    </p>

                    <form id="form-import-modal" method="POST"
                        action="{{ route('planillas.crearImport') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="import_id" id="import_id">

                        <label
                            class="block text-sm font-medium text-gray-700 mb-1">Archivo
                            (.xlsx / .xls)</label>
                        <input type="file" name="file" id="file"
                            accept=".xlsx,.xls"
                            class="w-full border rounded px-3 py-2 mb-4"
                            required>

                        <label
                            class="block text-sm font-medium text-gray-700 mb-1">Día
                            de aprobación</label>
                        <input type="date" name="fecha_aprobacion"
                            id="fecha_aprobacion"
                            class="w-full border rounded px-3 py-2 mb-4"
                            required>

                        {{-- Barra de progreso --}}
                        <div id="import-progress-wrap" class="hidden mb-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span id="import-progress-text"
                                    class="text-gray-700">Progreso</span>
                                <span id="import-progress-percent"
                                    class="text-gray-500">0%</span>
                            </div>
                            <div
                                class="w-full bg-gray-200 rounded h-3 overflow-hidden">
                                <div id="import-progress-bar"
                                    class="h-3 bg-blue-600 transition-all duration-200"
                                    style="width:0%"></div>
                            </div>
                            <p id="import-progress-msg"
                                class="text-xs text-gray-500 mt-1"></p>
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
                (function() {
                    const btnAbrir = document.getElementById('btn-abrir-import');
                    const modal = document.getElementById('modal-import');
                    const overlay = document.getElementById('modal-import-overlay');
                    const btnCancel = document.getElementById('btn-cancelar-import');
                    const form = document.getElementById('form-import-modal');
                    const inputFecha = document.getElementById('fecha_aprobacion');

                    const wrap = document.getElementById('import-progress-wrap');
                    const bar = document.getElementById('import-progress-bar');
                    const pct = document.getElementById('import-progress-percent');
                    const msg = document.getElementById('import-progress-msg');
                    const txt = document.getElementById('import-progress-text');
                    const btnSend = document.getElementById('btn-confirmar-import');
                    const importIdInput = document.getElementById('import_id');

                    function abrir() {
                        modal.classList.remove('hidden');
                        // Generar UUID cuando se abre el modal
                        importIdInput.value = uuidv4();
                    }

                    function cerrar() {
                        modal.classList.add('hidden');
                    }

                    const hoy = new Date().toISOString().split('T')[0];
                    inputFecha.value = hoy;
                    inputFecha.max = hoy;

                    btnAbrir.addEventListener('click', abrir);
                    overlay.addEventListener('click', cerrar);
                    btnCancel.addEventListener('click', cerrar);

                    function uuidv4() {
                        // navegador moderno
                        if (crypto?.randomUUID) return crypto.randomUUID();
                        // fallback
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
                        const url =
                            `/api/planillas/import/progress/${encodeURIComponent(importId)}`;
                        pollTimer = setInterval(async () => {
                            try {
                                const res = await fetch(url, {
                                    cache: 'no-store'
                                });
                                if (!res.ok) return;
                                const data = await res.json();
                                const percent = Math.min(100, Math.max(0,
                                    Math.round(data.percent ?? 0)));
                                bar.style.width = percent + '%';
                                pct.textContent = percent + '%';
                                msg.textContent = data.message ?? '';

                                if (data.status === 'error') {
                                    clearInterval(pollTimer);
                                    txt.textContent = 'Error';
                                    bar.classList.remove('bg-blue-600');
                                    bar.classList.add('bg-red-600');
                                }
                                // No recargamos automáticamente cuando llega al 100%
                                // El manejo se hace en el callback del submit
                                if (data.status === 'done' || percent >=
                                    100) {
                                    clearInterval(pollTimer);
                                    txt.textContent = 'Completado';
                                    bar.style.width = '100%';
                                    pct.textContent = '100%';
                                    msg.textContent =
                                        'Importación finalizada.';
                                }
                            } catch (e) {
                                /* silencioso */
                            }
                        }, 600);
                    }

                    form.addEventListener('submit', async function(ev) {
                        ev.preventDefault();
                        const importId = importIdInput.value;
                        startPolling(importId);
                        btnSend.disabled = true;
                        btnCancel.disabled = true;

                        const fd = new FormData(form);
                        try {
                            const res = await fetch(form.action, {
                                method: 'POST',
                                body: fd,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            const data = await res.json();

                            // Log para debug
                            console.log('Respuesta del servidor:', data);
                            console.log('Estadísticas recibidas:', data
                                .statistics);

                            // Detener polling
                            if (pollTimer) clearInterval(pollTimer);

                            if (res.ok && data.success) {
                                // Verificar si hubo errores a pesar del éxito
                                if (!data.errors || data.errors.length ===
                                    0) {
                                    // Todo perfecto - mostrar éxito
                                    const stats = data.statistics || {};
                                    const exitosas = stats.exitosas || 0;
                                    const elementos = stats
                                        .elementos_creados || 0;
                                    const etiquetas = stats
                                        .etiquetas_creadas || 0;
                                    const nombreArchivo = data
                                        .nombre_archivo || 'archivo';

                                    await Swal.fire({
                                        icon: 'success',
                                        title: '¡Importación exitosa!',
                                        html: `
                                            <div class="text-left space-y-3">
                                                <div class="bg-white p-3 rounded border border-gray-200">
                                                    <p class="text-gray-700"><strong>📄 Archivo:</strong> ${nombreArchivo}</p>
                                                    <p class="text-gray-700 mt-2"><strong>✅ Planillas importadas exitosamente:</strong> ${exitosas}</p>
                                                    <p class="text-gray-700 mt-2"><strong>📦 Elementos creados:</strong> ${elementos}</p>
                                                    <p class="text-gray-700 mt-2"><strong>🏷️ Etiquetas creadas:</strong> ${etiquetas}</p>
                                                </div>
                                            </div>
                                        `,
                                        confirmButtonText: 'OK',
                                        confirmButtonColor: '#3085d6',
                                        width: '500px'
                                    });
                                } else {
                                    // Éxito parcial - mostrar ambos resultados
                                    let html = '<div class="text-left">';

                                    if (data.statistics && data.statistics
                                        .exitosas > 0) {
                                        html += `
                                            <div class="bg-green-50 p-3 rounded mb-3">
                                                <p class="font-semibold text-green-800 mb-2">✅ Importadas correctamente:</p>
                                                <p class="text-sm text-green-700">${data.statistics.exitosas} planilla(s) procesada(s) exitosamente</p>
                                            </div>
                                        `;
                                    }

                                    html += `
                                        <div class="bg-red-50 p-3 rounded max-h-96 overflow-y-auto">
                                            <p class="font-semibold text-red-800 mb-2">❌ Errores detectados (${data.errors.length}):</p>
                                            <ul class="text-sm text-red-700 space-y-2">
                                    `;

                                    data.errors.forEach((error) => {
                                        html += `
                                            <li class="border-b border-red-200 pb-2 last:border-b-0">
                                                <strong>Planilla: ${error.codigo}</strong><br>
                                                <span class="text-xs">Error: ${error.error}</span>
                                            </li>
                                        `;
                                    });

                                    html += '</ul></div>';

                                    if (data.warnings && data.warnings
                                        .length > 0) {
                                        html += `
                                            <div class="bg-yellow-50 p-3 rounded mt-3">
                                                <p class="font-semibold text-yellow-800 mb-2">⚠️ Advertencias:</p>
                                                <ul class="text-sm text-yellow-700 space-y-1">
                                        `;

                                        data.warnings.forEach((warning) => {
                                            html +=
                                                `<li>• ${warning}</li>`;
                                        });

                                        html += '</ul></div>';
                                    }

                                    html += '</div>';

                                    await Swal.fire({
                                        icon: 'warning',
                                        title: 'Importación completada con errores',
                                        html: html,
                                        confirmButtonText: 'Entendido',
                                        confirmButtonColor: '#f59e0b',
                                        width: '600px'
                                    });
                                }

                                // Cerrar modal y resetear
                                cerrar();
                                form.reset();
                                inputFecha.value = hoy;
                                wrap.classList.add('hidden');
                                bar.style.width = '0%';
                                bar.classList.remove('bg-red-600');
                                bar.classList.add('bg-blue-600');
                                btnSend.disabled = false;
                                btnCancel.disabled = false;

                                // Recargar página para ver resultados
                                window.location.reload();

                            } else {
                                // Error completo - ninguna planilla importada correctamente
                                let errorHtml = '<div class="text-left">';

                                if (data.message) {
                                    errorHtml +=
                                        `<p class="mb-3 font-semibold">${data.message}</p>`;
                                }

                                if (data.errors && data.errors.length > 0) {
                                    errorHtml += `
                                        <div class="bg-red-50 p-3 rounded max-h-96 overflow-y-auto">
                                            <p class="font-semibold text-red-800 mb-2">❌ Errores detectados (${data.errors.length}):</p>
                                            <ul class="text-sm text-red-700 space-y-2">
                                    `;

                                    data.errors.forEach((error) => {
                                        errorHtml += `
                                            <li class="border-b border-red-200 pb-2 last:border-b-0">
                                                <strong>Planilla: ${error.codigo}</strong><br>
                                                <span class="text-xs">Error: ${error.error}</span>
                                            </li>
                                        `;
                                    });

                                    errorHtml += '</ul></div>';
                                }

                                if (data.warnings && data.warnings.length >
                                    0) {
                                    errorHtml += `
                                        <div class="bg-yellow-50 p-3 rounded mt-3">
                                            <p class="font-semibold text-yellow-800 mb-2">⚠️ Advertencias:</p>
                                            <ul class="text-sm text-yellow-700 space-y-1">
                                    `;

                                    data.warnings.forEach((warning) => {
                                        errorHtml +=
                                            `<li>• ${warning}</li>`;
                                    });

                                    errorHtml += '</ul></div>';
                                }

                                errorHtml += '</div>';

                                await Swal.fire({
                                    icon: 'error',
                                    title: 'Error en la importación',
                                    html: errorHtml,
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#d33',
                                    width: '600px'
                                });

                                // Resetear el formulario pero mantener modal abierto
                                wrap.classList.add('hidden');
                                bar.style.width = '0%';
                                bar.classList.remove('bg-red-600');
                                bar.classList.add('bg-blue-600');
                                btnSend.disabled = false;
                                btnCancel.disabled = false;
                            }

                        } catch (e) {
                            // Detener polling en caso de error
                            if (pollTimer) clearInterval(pollTimer);

                            console.error('Error en la importación:', e);

                            await Swal.fire({
                                icon: 'error',
                                title: 'Error de conexión',
                                text: 'No se pudo completar la importación. Por favor, intenta nuevamente.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#d33'
                            });

                            wrap.classList.add('hidden');
                            bar.style.width = '0%';
                            bar.classList.remove('bg-red-600');
                            bar.classList.add('bg-blue-600');
                            btnSend.disabled = false;
                            btnCancel.disabled = false;
                        }
                    });
                })();
            </script>


            <style>
                .cursor-espera * {
                    cursor: wait !important;
                    pointer-events: none !important;
                }
            </style>

            <form action="{{ route('planillas.completarTodas') }}"
                method="POST"
                onsubmit="return confirm('¿Completar todas las planillas pendientes?');">
                @csrf
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Completar todas las planillas
                </button>
            </form>


        </div>


        <!-- Badge de planillas sin revisar -->
        @if ($planillasSinRevisar > 0)
            <div
                class="mb-4 bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded-r-lg shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">⚠️</span>
                        <div>
                            <h3 class="text-lg font-bold text-yellow-800">
                                {{ $planillasSinRevisar }}
                                {{ $planillasSinRevisar === 1 ? 'planilla pendiente' : 'planillas pendientes' }}
                                de
                                revisión
                            </h3>
                            <p class="text-sm text-yellow-700">
                                Las planillas sin revisar aparecen en
                                <strong>GRIS</strong> en el calendario de
                                producción
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('planillas.index', ['revisada' => '0']) }}"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded transition-colors">
                        Ver planillas sin revisar
                    </a>
                </div>
            </div>
        @endif


        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <!-- TABLA DE PLANILLAS -->
        <div x-data="{ modalReimportar: false, planillaId: null }"
            class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table
                class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-4">
                    <tr class="text-center text-xs uppercase">

                        <th class="p-2 border">ID</th>
                        <th class="p-2 border">{!! $ordenables['codigo'] !!}</th>
                        <th class="p-2 border">Codigo Cliente</th>
                        <th class="p-2 border">Cliente</th>
                        <th class="p-2 border">Código Obra</th>
                        <th class="p-2 border">Obra</th>
                        <th class="p-2 border">{!! $ordenables['seccion'] !!}</th>
                        <th class="p-2 border">Descripción</th>
                        <th class="p-2 border">{!! $ordenables['ensamblado'] !!}</th>
                        <th class="p-2 border">Comentario</th>
                        <th class="p-2 border">peso fabricado</th>
                        <th class="p-2 border">{!! $ordenables['peso_total'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['estado'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_inicio'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_finalizacion'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_importacion'] ?? '' !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_estimada_entrega'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['nombre_completo'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['revisada'] !!}</th>
                        <th class="p-2 border">Revisada por</th>
                        <th class="p-2 border">Fecha revisión</th>

                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <form method="GET"
                            action="{{ route('planillas.index') }}">

                            <th class="p-1 border">

                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="codigo"
                                    value="{{ request('codigo') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="codigo_cliente"
                                    value="{{ request('codigo_cliente') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="cliente"
                                    value="{{ request('cliente') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="cod_obra"
                                    value="{{ request('cod_obra') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="nom_obra"
                                    value="{{ request('nom_obra') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="seccion"
                                    value="{{ request('seccion') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="descripcion"
                                    value="{{ request('descripcion') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="ensamblado"
                                    value="{{ request('ensamblado') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="comentario"
                                    value="{{ request('comentario') }}" />
                            </th>
                            <th class="p-1 border"></th> {{-- Peso Fabricado --}}
                            <th class="p-1 border"></th> {{-- Peso Total --}}

                            <th class="p-1 border">
                                <x-tabla.select name="estado"
                                    :options="[
                                        'pendiente' => 'Pendiente',
                                        'fabricando' => 'Fabricando',
                                        'completada' => 'Completada',
                                        'montaje' => 'Montaje',
                                    ]" :selected="request('estado')"
                                    empty="Todos" />
                            </th>

                            <th class="p-1 border">
                                <x-tabla.input type="date"
                                    name="fecha_inicio"
                                    value="{{ request('fecha_inicio') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input type="date"
                                    name="fecha_finalizacion"
                                    value="{{ request('fecha_finalizacion') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input type="date"
                                    name="fecha_importacion"
                                    value="{{ request('fecha_importacion') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input type="date"
                                    name="fecha_estimada_entrega"
                                    value="{{ request('fecha_estimada_entrega') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="nombre_completo"
                                    value="{{ request('nombre_completo') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="revisada"
                                    :options="[
                                        '' => 'Todas',
                                        '1' => 'Sí',
                                        '0' => 'No',
                                    ]" :selected="request()->query('revisada', '')" />
                            </th>

                            <th class="p-1 border"></th>
                            <th class="p-1 border"></th>


                            <x-tabla.botones-filtro ruta="planillas.index" />
                        </form>
                    </tr>

                </thead>
                <tbody class="text-gray-700">
                    @forelse ($planillas as $planilla)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            planilla: @js($planilla),
                            original: JSON.parse(JSON.stringify(@js($planilla)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                planilla = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }"
                            @keydown.enter.stop="guardarCambios(planilla); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs leading-none uppercase">


                            <!-- ID -->
                            <td class="p-2 text-center border">

                                <span x-text="planilla.id"></span>

                            </td>
                            <!-- Código -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.codigo_limpio"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.codigo"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Código Cliente -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.cliente.codigo ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.cliente.codigo"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Cliente -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('clientes.index', ['id' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->cliente->empresa ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.cliente.empresa"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Código Obra -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.obra.cod_obra ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.obra.cod_obra"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- Obra -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('clientes.show', ['cliente' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->obra->obra ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.obra.obra"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Sección -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.seccion ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.seccion"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Descripción -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.descripcion ?? 'Sin descripción'"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.descripcion"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Ensamblado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.ensamblado ?? 'Sin datos'"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.ensamblado"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Comentario -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.comentario ?? ' '"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.comentario"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Peso Fabricado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(planilla.suma_peso_completados) || 0) + ' KG'"></span>

                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.suma_peso_completados"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Peso Total -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.peso_total_kg"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.peso_total"
                                    class="form-control form-control-sm">
                            </td>


                            <!-- Estado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.estado.toUpperCase()"></span>
                                </template>
                                <select x-show="editando"
                                    x-model="planilla.estado"
                                    class="form-select w-full">
                                    <option value="pendiente">Pendiente
                                    </option>
                                    <option value="fabricando">Fabricando
                                    </option>
                                    <option value="completada">Completada
                                    </option>
                                </select>
                            </td>

                            <!-- Fecha Inicio -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.fecha_inicio"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.fecha_inicio"
                                    class="form-control form-control-sm"
                                    placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Fecha Finalización -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.fecha_finalizacion"></span>
                                </template>
                                <input x-show="editando" type="text"
                                    x-model="planilla.fecha_finalizacion"
                                    class="form-control form-control-sm"
                                    placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Fecha Importación -->
                            <td class="p-2 text-center border">
                                <span
                                    x-text="new Date(planilla.created_at).toLocaleDateString()"></span>
                            </td>

                            <!-- Fecha Entrega -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="planilla.fecha_estimada_entrega"></span>
                                </template>

                                <input x-show="editando" type="text"
                                    x-model="planilla.fecha_estimada_entrega"
                                    class="form-control form-control-sm"
                                    placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Usuario -->
                            <td class="p-2 text-center border">
                                <span
                                    x-text="planilla.user?.nombre_completo ?? 'Desconocido'"></span>
                            </td>

                            {{-- Revisiones --}}
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        :class="planilla.revisada ?
                                            'bg-green-100 text-green-700' :
                                            'bg-gray-100 text-gray-600'"
                                        class="px-2 py-1 rounded text-[11px] font-semibold inline-flex items-center gap-1">
                                        <span
                                            x-text="planilla.revisada ? 'Sí' : 'No'"></span>
                                    </span>
                                </template>

                                <label x-show="editando"
                                    class="inline-flex items-center gap-2">
                                    <input type="checkbox"
                                        x-model="planilla.revisada"
                                        class="w-4 h-4 accent-blue-600">
                                    <span>Revisada</span>
                                </label>
                            </td>
                            <!-- Revisor -->
                            <td class="p-2 text-center border">
                                <span
                                    x-text="planilla.revisor?.nombre_completo ?? '—'"></span>
                            </td>
                            <!-- Fecha Revisión -->
                            <td class="p-2 text-center border">
                                <template x-if="planilla.revisada_at">
                                    <span
                                        x-text="new Date(planilla.revisada_at).toLocaleString()"></span>
                                </template>
                                <template x-if="!planilla.revisada_at">
                                    <span>—</span>
                                </template>
                            </td>

                            <!-- Acciones Fila -->
                            <td class="px-2 py-2 border text-xs font-bold">
                                <div
                                    class="flex items-center space-x-2 justify-center">
                                    <!-- Mostrar solo en modo edición -->
                                    <x-tabla.boton-guardar x-show="editando"
                                        @click="guardarCambios(planilla); editando = false" />
                                    <x-tabla.boton-cancelar-edicion
                                        @click="editando = false"
                                        x-show="editando" />

                                    <!-- Mostrar solo cuando NO está en modo edición -->
                                    <template x-if="!editando">
                                        <div
                                            class="flex items-center space-x-2">
                                            <!-- Botón Reimportar -->
                                            <button
                                                @click="modalReimportar = true; planillaId = {{ $planilla->id }}"
                                                class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                                                title="Reimportar Planilla">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M4 4v6h6M20 20v-6h-6M4 20l4.586-4.586M20 4l-4.586 4.586" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="
    planilla.revisada = !planilla.revisada;
    guardarCambios(planilla);
  "
                                                class="w-6 h-6 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 flex items-center justify-center"
                                                title="Marcar como revisada (José Amuedo)">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor">
                                                    <path
                                                        d="M9 12l2 2 4-4-1.5-1.5L11 11l-1.5-1.5L8 11l1 1z" />
                                                </svg>
                                            </button>

                                            {{-- <!-- Botón Completar -->
                                            <button @click="completarPlanilla({{ $planilla->id }})"
                                                class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                                title="Completar Planilla">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button> --}}

                                            <x-tabla.boton-editar
                                                @click="editando = true"
                                                x-show="!editando" />
                                            <x-tabla.boton-ver
                                                :href="route(
                                                    'planillas.show',
                                                    $planilla->id,
                                                )" />
                                            <x-tabla.boton-eliminar
                                                :action="route(
                                                    'planillas.destroy',
                                                    $planilla->id,
                                                )" />

                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15"
                                class="text-center py-4 text-gray-500">No hay
                                planillas
                                disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr
                        class="bg-gradient-to-r from-blue-50 to-blue-100 border-t border-blue-300">
                        <td colspan="100%" class="px-6 py-3"
                            style="padding: 0" colspan="999">
                            <div
                                class="flex justify-end items-center gap-4 px-6 py-3 text-sm text-gray-700">
                                <span class="font-semibold">Total peso
                                    filtrado:</span>
                                <span
                                    class="text-base font-bold text-blue-800">
                                    {{ number_format($totalPesoFiltrado, 2, ',', '.') }}
                                    kg
                                </span>
                            </div>
                        </td>
                    </tr>
                </tfoot>



            </table>
            <!-- Modal Reimportar Planilla -->
            <div @keydown.escape.window="modalReimportar = false">
                <div x-show="modalReimportar"
                    class="fixed inset-0 bg-black bg-opacity-50 z-40" x-cloak>
                </div>

                <div x-show="modalReimportar"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="fixed z-50 top-1/2 left-1/2 w-11/12 max-w-lg transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-lg"
                    x-cloak>

                    <div class="px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">📤
                            Añade modificaciones del cliente</h2>

                        <form method="POST"
                            :action="`/planillas/${planillaId}/reimportar`"
                            enctype="multipart/form-data"
                            @submit="modalReimportar = false"
                            class="space-y-4">
                            @csrf

                            <div>
                                <label for="archivo"
                                    class="block text-sm font-medium text-gray-700">Selecciona
                                    el
                                    nuevo archivo:</label>
                                <input type="file" name="archivo"
                                    id="archivo" accept=".csv,.xlsx,.xls"
                                    required
                                    class="mt-1 block w-full border border-gray-300 rounded p-2 text-sm">
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="button"
                                    @click="modalReimportar = false"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                                    🔄 Reimportar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <x-tabla.paginacion :paginador="$planillas" />

    <script>
        function guardarCambios(planilla) {
            fetch(`{{ route('planillas.update', '') }}/${planilla.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify(planilla)
                })
                .then(async response => {
                    const contentType = response.headers.get(
                        'content-type');
                    let data = {};

                    if (contentType && contentType.includes(
                            'application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        throw new Error(
                            "El servidor devolvió una respuesta inesperada: " +
                            text.slice(0,
                                100)); // corta para no saturar
                    }

                    if (response.ok && data.success) {
                        window.location.reload();
                    } else {
                        let errorMsg = data.message ||
                            "Ha ocurrido un error inesperado.";
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat()
                                .join("<br>");
                        }

                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            html: errorMsg,
                            confirmButtonText: "OK",
                            showCancelButton: true,
                            cancelButtonText: "Reportar Error"
                        }).then((result) => {
                            if (result.dismiss === Swal
                                .DismissReason.cancel) {
                                notificarProgramador(errorMsg);
                            }
                        });
                    }
                })
                .catch(error => {
                    // Este catch ahora captura errores de red y errores de tipo (como HTML no válido)
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: error.message ||
                            "No se pudo actualizar la planilla. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }
    </script>
    <script>
        async function completarPlanilla(planillaId) {
            Swal.fire({
                title: '¿Completar planilla?',
                text: "Esta acción marcará la planilla, sus etiquetas y elementos como completados y la eliminará de la cola.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, completar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const res = await fetch(
                            '/planillas/completar', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        .getAttribute('content')
                                },
                                body: JSON.stringify({
                                    id: planillaId
                                })
                            });

                        const data = await res.json();

                        if (data.success) {
                            Swal.fire({
                                title: '¡Completada!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Refrescar la tabla o la página
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message ||
                                'Error al completar la planilla',
                                'error');
                        }
                    } catch (error) {
                        console.error(error);
                        Swal.fire('Error',
                            'Error de conexión al completar la planilla',
                            'error');
                    }
                }
            });
        }
    </script>
</x-app-layout>
