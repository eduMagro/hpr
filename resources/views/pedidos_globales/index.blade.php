<x-app-layout>
    <x-slot name="title">Pedidos Globales - {{ config('app.name') }}</x-slot>
    <div class="px-4 py-6">

        <button onclick="abrirModalPedidoGlobal()"
            class="px-4 py-2 mb-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
            ➕ Crear Pedido Global
        </button>

        @livewire('pedidos-globales-table')
    </div>

    {{-- =========================
         MODAL CREAR PEDIDO GLOBAL
       ========================= --}}
    <div id="modalPedidoGlobal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg relative">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Nuevo Pedido Global</h3>

            <form id="formPedidoGlobal">
                @csrf

                <div class="mb-3">
                    <label for="cantidad_total" class="block text-sm font-medium text-gray-700">Cantidad Total
                        (kg)</label>
                    <input type="number" name="cantidad_total" step="5000"
                        class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div class="mb-3">
                    <label for="fabricante_id" class="block text-sm font-medium text-gray-700">Fabricante</label>
                    <select name="fabricante_id" class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccionar --</option>
                        @foreach (\App\Models\Fabricante::orderBy('nombre')->get() as $fabricante)
                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="distribuidor_id" class="block text-sm font-medium text-gray-700">Distribuidor</label>
                    <select name="distribuidor_id" class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccionar --</option>
                        @foreach (\App\Models\Distribuidor::orderBy('nombre')->get() as $distribuidor)
                            <option value="{{ $distribuidor->id }}">{{ $distribuidor->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="precio_referencia" class="block text-sm font-medium text-gray-700">Precio de
                        Referencia (€) - Opcional</label>
                    <input type="number" name="precio_referencia" step="0.01" min="0"
                        class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ej: 6.40">
                </div>

                <div class="text-right pt-4">
                    <button type="button" onclick="cerrarModalPedidoGlobal()"
                        class="mr-2 px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Crear Pedido Global
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================
         SCRIPTS
       ========================= --}}
    <script>
        function abrirModalPedidoGlobal() {
            document.getElementById('modalPedidoGlobal').classList.remove('hidden');
            document.getElementById('modalPedidoGlobal').classList.add('flex');
        }

        function cerrarModalPedidoGlobal() {
            document.getElementById('modalPedidoGlobal').classList.remove('flex');
            document.getElementById('modalPedidoGlobal').classList.add('hidden');
        }

        document.getElementById('formPedidoGlobal').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;

            fetch("{{ route('pedidos_globales.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: new FormData(form)
                })
                .then(async res => {
                    const contentType = res.headers.get("content-type") || '';
                    if (res.ok && contentType.includes("application/json")) {
                        await res.json();
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Pedido global creado correctamente.',
                            confirmButtonColor: '#16a34a'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        const text = await res.text();
                        throw new Error("Error inesperado:\n" + text.slice(0, 600));
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.message || 'Error creando pedido global.'
                    });
                });
        });
    </script>
</x-app-layout>
