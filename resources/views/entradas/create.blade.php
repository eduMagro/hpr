<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Entradas de Material') }}
        </h2>
    </x-slot>

    <!-- Manejo de Errores y Mensajes de Sesión -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Contenedor Principal -->
    <div class="container my-5">
        <form id="inventarioForm" method="POST" action="{{ route('inventario.guardar') }}">
            @csrf

            <label for="fabricante">Fabricante:</label>
            <input type="text" id="fabricante" name="fabricante" required>

            <label for="albaran">Número de Albarán:</label>
            <input type="text" id="albaran" name="albaran" required>

            <label for="peso_total">Peso Total (kg):</label>
            <input type="number" id="peso_total" name="peso_total" required>

            <label for="cantidad_paquetes">Cantidad de Paquetes:</label>
            <input type="number" id="cantidad_paquetes" name="cantidad_paquetes" min="1" required>

            <div id="paquetesContainer"></div>

            <button type="submit">Guardar</button>
        </form>

        <script>
            document.getElementById('cantidad_paquetes').addEventListener('input', function() {
                let cantidad = parseInt(this.value);
                let container = document.getElementById('paquetesContainer');
                container.innerHTML = '';

                if (cantidad > 0) {
                    for (let i = 1; i <= cantidad; i++) {
                        let div = document.createElement('div');
                        div.innerHTML = `
                    <h3>Paquete ${i}</h3>
                    <label for="codigo_${i}">Código:</label>
                    <input type="text" id="codigo_${i}" name="paquetes[${i}][codigo]" required>
                    
                    <label for="planilla_${i}">Número de Planilla:</label>
                    <input type="text" id="planilla_${i}" name="paquetes[${i}][planilla]" required>
                    
                    <label for="diametro_${i}">Diámetro:</label>
                    <input type="number" step="0.01" id="diametro_${i}" name="paquetes[${i}][diametro]" required>
                    
                    <label for="peso_${i}">Peso (kg):</label>
                    <input type="number" step="0.01" id="peso_${i}" name="paquetes[${i}][peso]" required>
                `;
                        container.appendChild(div);
                    }
                }
            });
        </script>

</x-app-layout>
