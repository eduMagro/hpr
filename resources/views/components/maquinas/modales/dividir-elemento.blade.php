<div id="modalDividirElemento"
    class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">✂️ Dividir Elemento</h2>

        <form id="formDividirElemento" method="POST">
            @csrf
            <input type="hidden" name="elemento_id" id="dividir_elemento_id">

            <label for="num_nuevos" class="block text-sm font-medium text-gray-700 mb-1">
                ¿Cuántos nuevos grupos de elementos quieres crear?
            </label>
            <input type="number" name="num_nuevos" id="num_nuevos" class="w-full border rounded p-2 mb-4"
                min="1" placeholder="Ej: 2">

            <div class="flex justify-end mt-4">
                <button type="button" onclick="document.getElementById('modalDividirElemento').classList.add('hidden')"
                    class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                    Cancelar
                </button>
                <button type="button" onclick="enviarDivision()"
                    class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                    Dividir
                </button>
            </div>
        </form>
    </div>
</div>
