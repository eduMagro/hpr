<div id="modalDividirElemento"
    class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center z-50">

    <div class="bg-white p-8 rounded-xl shadow-xl w-[520px] max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-gray-800 mb-5">Gestión de elemento</h2>

        <form id="formDividirElemento" method="POST">
            @csrf
            <input type="hidden" name="elemento_id" id="dividir_elemento_id">
            <input type="hidden" name="barras_totales" id="dividir_barras_totales">
            <input type="hidden" name="peso_total" id="dividir_peso_total">

            <label class="block text-base font-medium text-gray-700 mb-3">¿Qué quieres hacer?</label>
            <div class="flex flex-col gap-3 mb-5">
                <label class="inline-flex items-center gap-2 text-base cursor-pointer hover:bg-gray-50 p-2 rounded">
                    <input type="radio" name="accion_etiqueta" value="dividir" checked
                        onchange="toggleCamposDivision()" class="w-4 h-4">
                    <span>✂️ Dividir barras en otra etiqueta</span>
                </label>
                <label class="inline-flex items-center gap-2 text-base cursor-pointer hover:bg-gray-50 p-2 rounded">
                    <input type="radio" name="accion_etiqueta" value="mover" onchange="toggleCamposDivision()" class="w-4 h-4">
                    <span>➡️ Pasar todo a una nueva etiqueta</span>
                </label>
                <label class="inline-flex items-center gap-2 text-base cursor-pointer hover:bg-gray-50 p-2 rounded transition-opacity duration-200" id="labelCambiarMaquina">
                    <input type="radio" name="accion_etiqueta" value="cambiar_maquina" onchange="toggleCamposDivision()" class="w-4 h-4">
                    <span>🔄 Mandar a otra máquina</span>
                </label>
                <label class="inline-flex items-center gap-2 text-base cursor-pointer hover:bg-gray-50 p-2 rounded">
                    <input type="radio" name="accion_etiqueta" value="ver_dimensiones" onchange="toggleCamposDivision()" class="w-4 h-4">
                    <span>📐 Ver dimensiones del elemento</span>
                </label>
            </div>

            <div id="campoDivision" class="block">
                <div id="infoBarrasActuales" class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-base text-blue-800">
                        <span class="font-semibold">Barras actuales:</span>
                        <span id="labelBarrasActuales" class="text-2xl font-bold ml-2">-</span>
                    </p>
                </div>

                {{-- Badge de sugerencia para peso máximo de 1200kg --}}
                <div id="badgeSugerenciaPeso" class="hidden mb-4 p-4 bg-amber-50 rounded-lg border-2 border-amber-400">
                    <p class="text-base text-amber-800 mb-2">
                        <span class="font-bold">⚖️ Sugerencia (máx 1200 kg/paquete):</span>
                    </p>
                    <p class="text-lg text-amber-700">
                        Necesitas <span id="barrasSugeridas" class="text-2xl font-bold text-amber-900">-</span>
                    </p>
                    <p id="detalleSugerencia" class="text-sm text-amber-700 mt-2 leading-relaxed"></p>

                    {{-- Botón para dividir automáticamente --}}
                    <button type="button" id="btnDividirAuto" onclick="dividirAutomaticamente()"
                        class="mt-4 w-full px-4 py-3 bg-amber-500 text-white text-base font-bold rounded-lg hover:bg-amber-600 transition flex items-center justify-center gap-2">
                        <span>⚡</span>
                        <span>Dividir automáticamente</span>
                    </button>
                    <input type="hidden" id="divisionAutoData" value="">
                </div>

                <label for="barras_a_mover" class="block text-base font-medium text-gray-700 mb-2">
                    ¿Cuántas barras quieres pasar a otra etiqueta?
                </label>
                <input type="number" name="barras_a_mover" id="barras_a_mover" class="w-full border-2 rounded-lg p-3 text-lg" min="1"
                    placeholder="Ej: 20">
                <p id="previewDivision" class="text-sm text-gray-500 mt-3 hidden"></p>
            </div>

            {{-- Campo para seleccionar máquina destino --}}
            <div id="campoCambiarMaquina" class="hidden">
                <div id="infoDiametroElemento" class="mb-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
                    <p class="text-base text-amber-800">
                        <span class="font-semibold">Diámetro del elemento:</span>
                        <span id="labelDiametroElemento" class="text-2xl font-bold ml-2">-</span>
                    </p>
                </div>

                <label for="maquina_destino" class="block text-base font-medium text-gray-700 mb-2">
                    Selecciona la máquina destino:
                </label>
                <select name="maquina_destino" id="maquina_destino"
                    class="w-full border-2 rounded-lg p-3 text-base bg-white focus:ring-2 focus:ring-purple-500">
                    <option value="">Cargando máquinas...</option>
                </select>
                <p id="infoMaquinaActual" class="text-sm text-gray-500 mt-3"></p>
            </div>

            <div class="flex justify-end mt-8 gap-3">
                <button type="button" onclick="document.getElementById('modalDividirElemento').classList.add('hidden')"
                    class="px-6 py-3 bg-gray-500 text-white text-base font-medium rounded-lg hover:bg-gray-600 transition">
                    Cancelar
                </button>
                <button type="button" onclick="enviarAccionEtiqueta()"
                    class="px-6 py-3 bg-purple-600 text-white text-base font-medium rounded-lg hover:bg-purple-700 transition">
                    Aceptar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Variable para almacenar las máquinas cargadas
    let maquinasDisponiblesCache = null;

    // Función para dividir automáticamente en múltiples etiquetas
    async function dividirAutomaticamente() {
        const dataInput = document.getElementById('divisionAutoData');
        if (!dataInput || !dataInput.value) {
            if (window.Swal) {
                Swal.fire('Error', 'No hay datos de división disponibles', 'error');
            }
            return;
        }

        const data = JSON.parse(dataInput.value);
        const { elemento_id, num_etiquetas, barras_por_etiqueta, etiquetas_con_barra_extra, barras_totales } = data;

        // Confirmar la acción
        if (window.Swal) {
            const confirmacion = await Swal.fire({
                icon: 'question',
                title: '¿Dividir automáticamente?',
                html: `Se crearán <strong>${num_etiquetas - 1} nuevas etiquetas</strong> a partir de este elemento.<br><br>
                       <small class="text-gray-500">El elemento original mantendrá ${etiquetas_con_barra_extra > 0 ? barras_por_etiqueta + 1 : barras_por_etiqueta} barras.</small>`,
                showCancelButton: true,
                confirmButtonText: 'Sí, dividir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f59e0b',
            });

            if (!confirmacion.isConfirmed) return;

            // Mostrar loading
            Swal.fire({
                title: 'Dividiendo elemento...',
                html: 'Creando etiquetas <b>0</b> de <b>' + (num_etiquetas - 1) + '</b>',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        }

        try {
            const resp = await fetch('{{ route("elementos.dividir-auto") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                },
                body: JSON.stringify({
                    elemento_id: elemento_id,
                    num_etiquetas: num_etiquetas,
                    barras_por_etiqueta: barras_por_etiqueta,
                    etiquetas_con_barra_extra: etiquetas_con_barra_extra
                })
            });

            const result = await resp.json();

            if (!resp.ok || result.success === false) {
                throw new Error(result.message || 'Error al dividir automáticamente');
            }

            // Cerrar modal
            document.getElementById('modalDividirElemento').classList.add('hidden');

            // Refrescar para que las etiquetas estén disponibles para imprimir
            if (typeof window.refrescarEtiquetasMaquina === 'function') {
                await window.refrescarEtiquetasMaquina();
                // Esperar a que los SVGs se rendericen
                await new Promise(resolve => setTimeout(resolve, 800));
            }

            // Preguntar si quiere imprimir todas las etiquetas
            if (window.Swal && result.todas_las_etiquetas && result.todas_las_etiquetas.length > 0) {
                const preguntaImprimir = await Swal.fire({
                    icon: 'success',
                    title: '¡División completada!',
                    html: result.message + '<br><br><strong>¿Deseas imprimir todas las etiquetas?</strong>',
                    showCancelButton: true,
                    confirmButtonText: '🖨️ Sí, imprimir todas',
                    cancelButtonText: 'No, gracias',
                    confirmButtonColor: '#10b981',
                });

                if (preguntaImprimir.isConfirmed) {
                    // Mostrar loading mientras se imprime
                    Swal.fire({
                        title: 'Preparando impresión...',
                        html: `Imprimiendo ${result.todas_las_etiquetas.length} etiquetas`,
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    // Llamar a la función de impresión si existe
                    if (typeof window.imprimirEtiquetas === 'function') {
                        await window.imprimirEtiquetas(result.todas_las_etiquetas, 'a6');
                    }

                    Swal.close();
                }
            } else if (window.Swal) {
                // Mostrar solo el éxito si no hay etiquetas para imprimir
                await Swal.fire({
                    icon: 'success',
                    title: '¡División completada!',
                    html: result.message,
                    timer: 3000,
                    showConfirmButton: false
                });
            }

            // Refrescar de nuevo si no se hizo antes
            if (typeof window.refrescarEtiquetasMaquina !== 'function') {
                window.location.reload();
            }

        } catch (e) {
            if (window.Swal) {
                Swal.fire('Error', e.message, 'error');
            } else {
                alert(e.message);
            }
        }
    }

    function toggleCamposDivision() {
        const accion = document.querySelector('input[name="accion_etiqueta"]:checked').value;
        document.getElementById('campoDivision').style.display = (accion === 'dividir') ? 'block' : 'none';
        document.getElementById('campoCambiarMaquina').style.display = (accion === 'cambiar_maquina') ? 'block' : 'none';

        // Si se selecciona cambiar máquina, cargar las opciones
        if (accion === 'cambiar_maquina') {
            cargarMaquinasDisponibles();
        }
    }

    async function cargarMaquinasDisponibles() {
        const elementoId = document.getElementById('dividir_elemento_id').value;
        const select = document.getElementById('maquina_destino');
        const labelDiametro = document.getElementById('labelDiametroElemento');
        const infoMaquinaActual = document.getElementById('infoMaquinaActual');

        if (!elementoId) {
            select.innerHTML = '<option value="">Error: No hay elemento seleccionado</option>';
            return;
        }

        select.innerHTML = '<option value="">Cargando máquinas...</option>';

        try {
            const resp = await fetch(`/elementos/${elementoId}/maquinas-disponibles`);
            const data = await resp.json();

            if (!data.success) {
                throw new Error(data.message || 'Error al cargar máquinas');
            }

            // Mostrar diámetro del elemento
            labelDiametro.textContent = `Ø${data.elemento.diametro}`;

            // Construir opciones del select
            let optionsHtml = '<option value="">-- Selecciona una máquina --</option>';
            let maquinaActualCodigo = '';

            data.maquinas.forEach(m => {
                const esActual = m.es_actual;
                if (esActual) {
                    maquinaActualCodigo = m.codigo;
                }
                const disabled = esActual ? 'disabled' : '';
                const label = esActual
                    ? `${m.codigo} (actual)`
                    : `${m.codigo} - Ø${m.diametro_min || '?'}-${m.diametro_max || '?'}`;
                optionsHtml += `<option value="${m.id}" ${disabled}>${label}</option>`;
            });

            select.innerHTML = optionsHtml;

            // Mostrar info de máquina actual
            if (maquinaActualCodigo) {
                infoMaquinaActual.innerHTML = `<span class="text-amber-600">📍 Máquina actual: <strong>${maquinaActualCodigo}</strong></span>`;
            } else {
                infoMaquinaActual.innerHTML = '<span class="text-gray-500">Sin máquina asignada actualmente</span>';
            }

            maquinasDisponiblesCache = data.maquinas;

        } catch (e) {
            console.error('Error al cargar máquinas:', e);
            select.innerHTML = '<option value="">Error al cargar máquinas</option>';
        }
    }

    // Actualizar preview cuando cambia el numero de barras a mover
    document.getElementById('barras_a_mover')?.addEventListener('input', function() {
        const barrasTotales = parseInt(document.getElementById('dividir_barras_totales').value) || 0;
        const barrasAMover = parseInt(this.value) || 0;
        const preview = document.getElementById('previewDivision');

        if (barrasAMover > 0 && barrasAMover < barrasTotales) {
            const quedanOriginal = barrasTotales - barrasAMover;
            preview.innerHTML = `<span class="text-green-600">✓ Etiqueta original: <strong>${quedanOriginal}</strong> barras | Nueva etiqueta: <strong>${barrasAMover}</strong> barras</span>`;
            preview.classList.remove('hidden');
        } else if (barrasAMover >= barrasTotales) {
            preview.innerHTML = `<span class="text-red-600">✗ No puedes mover todas o más barras de las que tiene</span>`;
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    });

    async function enviarAccionEtiqueta() {
        const elementoId = document.getElementById('dividir_elemento_id').value;
        const accion = document.querySelector('input[name="accion_etiqueta"]:checked').value;

        if (!elementoId) {
            alert('Falta el ID del elemento.');
            return;
        }

        try {
            if (accion === 'ver_dimensiones') {
                // Cerrar el modal actual
                document.getElementById('modalDividirElemento').classList.add('hidden');

                // Abrir el modal de ver dimensiones
                if (typeof window.abrirModalVerDimensiones === 'function') {
                    window.abrirModalVerDimensiones(elementoId);
                } else {
                    alert('La función de ver dimensiones no está disponible');
                }
                return;
            }

            if (accion === 'cambiar_maquina') {
                const maquinaDestinoId = document.getElementById('maquina_destino').value;

                if (!maquinaDestinoId) {
                    if (window.Swal) {
                        Swal.fire('Atención', 'Selecciona una máquina destino', 'warning');
                    } else {
                        alert('Selecciona una máquina destino');
                    }
                    return;
                }

                // Confirmar el cambio
                const maquinaSeleccionada = maquinasDisponiblesCache?.find(m => m.id == maquinaDestinoId);
                const nombreMaquina = maquinaSeleccionada?.codigo || 'seleccionada';

                if (window.Swal) {
                    const confirmacion = await Swal.fire({
                        icon: 'question',
                        title: '¿Cambiar máquina?',
                        html: `El elemento se moverá a la máquina <strong>${nombreMaquina}</strong>.<br><br>
                               <small class="text-gray-500">Esto puede crear una nueva subetiqueta o agrupar con elementos similares (MSR20).</small>`,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cambiar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#7c3aed',
                    });

                    if (!confirmacion.isConfirmed) return;
                }

                // Mostrar loading
                if (window.Swal) {
                    Swal.fire({
                        title: 'Cambiando máquina...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                }

                const resp = await fetch(`/elementos/${elementoId}/cambiar-maquina`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    },
                    body: JSON.stringify({
                        maquina_id: maquinaDestinoId
                    })
                });

                const data = await resp.json();
                if (!resp.ok || data.success === false) {
                    throw new Error(data.message || 'Error al cambiar máquina');
                }

                // Mostrar mensaje de éxito
                if (window.Swal) {
                    const elementosMovidos = data.elementos_movidos || 1;
                    await Swal.fire({
                        icon: 'success',
                        title: 'Máquina cambiada',
                        html: `<p>${data.message}</p>
                               <p class="text-sm text-gray-500 mt-2">
                                   ${data.maquina_anterior} → ${data.maquina_nueva}
                               </p>
                               ${elementosMovidos > 1 ? `<p class="text-xs text-blue-500 mt-1">(Grupo resumido: ${elementosMovidos} elementos similares)</p>` : ''}`,
                        timer: 3500,
                        showConfirmButton: false
                    });
                }

                // Cerrar modal y refrescar
                document.getElementById('modalDividirElemento').classList.add('hidden');

                if (typeof window.refrescarEtiquetasMaquina === 'function') {
                    window.refrescarEtiquetasMaquina();
                } else {
                    window.location.reload();
                }
                return;
            }

            if (accion === 'dividir') {
                const barrasTotales = parseInt(document.getElementById('dividir_barras_totales').value) || 0;
                const barrasAMover = parseInt(document.getElementById('barras_a_mover').value || '0', 10);

                if (!barrasAMover || barrasAMover < 1) {
                    alert('Introduce un número válido de barras a mover.');
                    return;
                }

                if (barrasAMover >= barrasTotales) {
                    alert('No puedes mover todas o más barras de las que tiene el elemento. Usa "Pasar todo a una nueva etiqueta" si quieres mover todo.');
                    return;
                }

                const resp = await fetch('{{ route('elementos.dividir') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    },
                    body: JSON.stringify({
                        elemento_id: elementoId,
                        barras_a_mover: barrasAMover
                    })
                });
                const data = await resp.json();
                if (!resp.ok || data.success === false) throw new Error(data.message || 'Error al dividir');

                // Mostrar mensaje de éxito
                if (window.Swal) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'División completada',
                        html: data.message,
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            } else if (accion === 'mover') {
                // mover todo a nueva subetiqueta
                const resp = await fetch('{{ route('subetiquetas.moverTodo') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    },
                    body: JSON.stringify({
                        elemento_id: elementoId
                    })
                });
                const data = await resp.json();
                if (!resp.ok || data.success === false) throw new Error(data.message ||
                    'Error al mover a nueva etiqueta');
            }

            // Cerrar modal y refrescar sin recargar la página
            document.getElementById('modalDividirElemento').classList.add('hidden');

            // Limpiar formulario
            document.getElementById('barras_a_mover').value = '';
            document.getElementById('previewDivision').classList.add('hidden');

            // Llamar a la función de refresco si existe
            if (typeof window.refrescarEtiquetasMaquina === 'function') {
                window.refrescarEtiquetasMaquina();
            } else {
                console.warn('window.refrescarEtiquetasMaquina no está definida, recargando página...');
                window.location.reload();
            }
        } catch (e) {
            if (window.Swal) {
                Swal.fire('Error', e.message, 'error');
            } else {
                alert(e.message);
            }
        }
    }
</script>
