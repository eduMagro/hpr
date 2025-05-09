<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">Previsualización del correo de Pedido</h2>
    </x-slot>

    <div class="py-6 px-4 max-w-4xl mx-auto">
        <div class="mb-4">
            <a href="{{ route('pedidos.index') }}" class="text-blue-600 hover:underline">&larr; Volver a pedidos</a>
        </div>

        <div class="bg-white shadow rounded p-4">
            {!! $mailPreview->render() !!}
        </div>

        <div class="mt-4 text-right">
            <form action="{{ route('pedidos.enviarCorreo', $pedido->id) }}" method="POST">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Enviar correo
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
