<div id="modalDividirElemento"
    class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Gestión de etiqueta</h2>

        <form id="formDividirElemento" method="POST">
            @csrf
            <input type="hidden" name="elemento_id" id="dividir_elemento_id">

            <label class="block text-sm font-medium text-gray-700 mb-2">¿Qué quieres hacer?</label>
            <div class="flex flex-col gap-2 mb-4">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="accion_etiqueta" value="dividir" checked
                        onchange="toggleCamposDivision()">
                    <span>✂️ Dividir en mas etiquetas</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="accion_etiqueta" value="mover" onchange="toggleCamposDivision()">
                    <span>➡️ Pasar todo a una nueva etiqueta</span>
                </label>
            </div>

            <div id="campoDivision" class="block">
                <label for="num_nuevos" class="block text-sm font-medium text-gray-700 mb-1">
                    ¿Cuántas etiquetas nuevas quieres crear?
                </label>
                <input type="number" name="num_nuevos" id="num_nuevos" class="w-full border rounded p-2" min="1"
                    placeholder="Ej: 2">
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

    async function enviarAccionEtiqueta() {
        const elementoId = document.getElementById('dividir_elemento_id').value;
        const accion = document.querySelector('input[name="accion_etiqueta"]:checked').value;

        if (!elementoId) {
            alert('Falta el ID del elemento.');
            return;
        }

        try {
            if (accion === 'dividir') {
                const num = parseInt(document.getElementById('num_nuevos').value || '0', 10);
                if (!num || num < 1) {
                    alert('Introduce un número válido de partes nuevas.');
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
                        num_nuevos: num
                    })
                });
                const data = await resp.json();
                if (!resp.ok || data.success === false) throw new Error(data.message || 'Error al dividir');
            } else {
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

            // Cerrar modal y refrescar (ajusta a tu flujo: recargar canvas/tabla, etc.)
            document.getElementById('modalDividirElemento').classList.add('hidden');
            if (typeof window.refrescarCanvasMaquina === 'function') {
                window.refrescarCanvasMaquina();
            } else {
                window.location.reload();
            }
        } catch (e) {
            alert(e.message);
        }
    }
</script>
