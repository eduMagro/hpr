<div id="modalDividirElemento"
    class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Gestion de etiqueta</h2>

        <form id="formDividirElemento" method="POST">
            @csrf
            <input type="hidden" name="elemento_id" id="dividir_elemento_id">
            <input type="hidden" name="barras_totales" id="dividir_barras_totales">

            <label class="block text-sm font-medium text-gray-700 mb-2">¿Que quieres hacer?</label>
            <div class="flex flex-col gap-2 mb-4">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="accion_etiqueta" value="dividir" checked
                        onchange="toggleCamposDivision()">
                    <span>✂️ Dividir barras en otra etiqueta</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="accion_etiqueta" value="mover" onchange="toggleCamposDivision()">
                    <span>➡️ Pasar todo a una nueva etiqueta</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="accion_etiqueta" value="ver_dimensiones" onchange="toggleCamposDivision()">
                    <span>📐 Ver dimensiones del elemento</span>
                </label>
            </div>

            <div id="campoDivision" class="block">
                <div id="infoBarrasActuales" class="mb-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm text-blue-800">
                        <span class="font-semibold">Barras actuales:</span>
                        <span id="labelBarrasActuales" class="text-lg font-bold">-</span>
                    </p>
                </div>

                <label for="barras_a_mover" class="block text-sm font-medium text-gray-700 mb-1">
                    ¿Cuantas barras quieres pasar a otra etiqueta?
                </label>
                <input type="number" name="barras_a_mover" id="barras_a_mover" class="w-full border rounded p-2" min="1"
                    placeholder="Ej: 20">
                <p id="previewDivision" class="text-xs text-gray-500 mt-2 hidden"></p>
            </div>

            <div class="flex justify-end mt-6">
                <button type="button" onclick="document.getElementById('modalDividirElemento').classList.add('hidden')"
                    class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                    Cancelar
                </button>
                <button type="button" onclick="enviarAccionEtiqueta()"
                    class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                    Aceptar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleCamposDivision() {
        const accion = document.querySelector('input[name="accion_etiqueta"]:checked').value;
        document.getElementById('campoDivision').style.display = (accion === 'dividir') ? 'block' : 'none';
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
            preview.innerHTML = `<span class="text-red-600">✗ No puedes mover todas o mas barras de las que tiene</span>`;
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
                    alert('La funcion de ver dimensiones no esta disponible');
                }
                return;
            }

            if (accion === 'dividir') {
                const barrasTotales = parseInt(document.getElementById('dividir_barras_totales').value) || 0;
                const barrasAMover = parseInt(document.getElementById('barras_a_mover').value || '0', 10);

                if (!barrasAMover || barrasAMover < 1) {
                    alert('Introduce un numero valido de barras a mover.');
                    return;
                }

                if (barrasAMover >= barrasTotales) {
                    alert('No puedes mover todas o mas barras de las que tiene el elemento. Usa "Pasar todo a una nueva etiqueta" si quieres mover todo.');
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

                // Mostrar mensaje de exito
                if (window.Swal) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Division completada',
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

            // Cerrar modal y refrescar sin recargar la pagina
            document.getElementById('modalDividirElemento').classList.add('hidden');

            // Limpiar formulario
            document.getElementById('barras_a_mover').value = '';
            document.getElementById('previewDivision').classList.add('hidden');

            // Llamar a la funcion de refresco si existe
            if (typeof window.refrescarEtiquetasMaquina === 'function') {
                window.refrescarEtiquetasMaquina();
            } else {
                console.warn('window.refrescarEtiquetasMaquina no esta definida, recargando pagina...');
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
