@props([
    'maquina', // máquina actual
    'maquinas', // listado de todas las máquinas
])

<div id="modalCambioMaquina" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
        <h2 class="text-lg font-semibold mb-4">Motivo del cambio de máquina</h2>

        <form id="formCambioMaquina" onsubmit="enviarCambioMaquina(event)">
            <input type="hidden" id="cambio-elemento-id" name="elemento_id">

            {{-- Motivo del cambio --}}
            <label for="motivoSelect" class="block font-semibold mb-1">Motivo del cambio:</label>
            <select id="motivoSelect" name="motivo" onchange="mostrarCampoOtro()"
                class="w-full border p-2 rounded mb-4" required>
                <option value="" disabled selected>Selecciona un motivo</option>
                <option value="Fallo técnico en máquina actual">Fallo técnico en máquina actual</option>
                <option value="Máquina saturada o con mucha carga">Máquina saturada o con mucha carga</option>
                <option value="Cambio de prioridad en producción">Cambio de prioridad en producción</option>
                <option value="Otros">Otros</option>
            </select>

            <div id="campoOtroMotivo" class="hidden mb-4">
                <label for="motivoTexto" class="block font-semibold mb-1">Especifica otro motivo:</label>
                <input type="text" id="motivoTexto" class="w-full border p-2 rounded"
                    placeholder="Escribe tu motivo">
            </div>

            {{-- Selección de máquina destino --}}
            <label for="maquinaDestino" class="block font-semibold mb-1">Máquina destino:</label>
            <select id="maquinaDestino" name="maquina_id" class="w-full border p-2 rounded mb-4" required>
                <option value="" disabled selected>Selecciona una máquina</option>
                @foreach ($maquinas as $m)
                    @if (in_array($m->tipo, ['cortadora_dobladora', 'estribadora']) && $m->id !== $maquina->id)
                        <option value="{{ $m->id }}">{{ $m->nombre }} ({{ $m->tipo }})</option>
                    @endif
                @endforeach
            </select>

            <div class="mt-4 text-right">
                <button type="button" onclick="onclick="cerrarModalCambio()" wire:navigate" class="mr-2 px-4 py-1 bg-gray-300 rounded">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                    Enviar
                </button>
            </div>
        </form>
    </div>
</div>
