<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Gestión de Salidas de Camiones') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- SECCIÓN: CREAR NUEVA SALIDA -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4 text-black">Registrar Nueva Salida</h1>

                <form action="{{ route('salidas.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="camion" class="block text-black font-semibold">Camión</label>
                        <input type="text" name="camion" id="camion" class="w-full p-2 border rounded mt-1" placeholder="Ej: Camión ABC123" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-black font-semibold">Seleccionar Planillas Completadas</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                            @foreach ($planillasCompletadas as $planilla)
                                <label class="flex items-center bg-gray-100 p-2 rounded-lg">
                                    <input type="checkbox" name="planillas[]" value="{{ $planilla->id }}" class="mr-2">
                                    <span class="text-gray-800">
                                        Planilla: <strong>{{ $planilla->codigo_limpio }}</strong> - Cliente: {{ $planilla->cliente }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Registrar Salida
                    </button>
                </form>
            </div>

            <!-- SECCIÓN: ESCANEO DE QR -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4 text-black">Escanear Carga del Camión</h1>
                
                <label class="block text-black font-semibold">Escanear QR:</label>
                <input type="text" id="qrInput" class="w-full p-2 border rounded mt-2" placeholder="Escanea el QR aquí">
                <button onclick="marcarSubido()" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 mt-2">
                    Confirmar
                </button>

                <div id="mensaje" class="mt-3 text-center"></div>
            </div>
        </div>

        <!-- TABLA: SEGUIMIENTO DE ESCANEOS -->
        <div class="mt-6 bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-4 text-black">Elementos Cargados</h1>
            <table class="w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody id="tablaSubidos" class="text-gray-700 text-sm">
                    <!-- Se llenará con JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- TABLA: HISTORIAL DE SALIDAS -->
        <div class="mt-6 bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-4 text-black">Historial de Salidas</h1>
            <table class="w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Camión</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Planillas Transportadas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Fecha de Salida</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @foreach ($salidas as $salida)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-6 py-4 text-black">{{ $salida->camion }}</td>
                            <td class="px-6 py-4 text-gray-800">
                                @foreach ($salida->planillas as $planilla)
                                    <span class="block">{{ $planilla->codigo_limpio }} - {{ $planilla->cliente }}</span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $salida->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function marcarSubido() {
            let codigo = document.getElementById("qrInput").value;
            if (!codigo) return alert("Por favor, escanea un código.");

            fetch("{{ route('escaneo.marcarSubido') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ codigo: codigo })
            })
            .then(response => response.json())
            .then(data => {
                let mensajeDiv = document.getElementById("mensaje");
                if (data.success) {
                    mensajeDiv.innerHTML = `<p class="text-green-600 font-semibold">${data.mensaje}</p>`;
                    agregarATabla(codigo);
                    document.getElementById("qrInput").value = "";
                } else {
                    mensajeDiv.innerHTML = `<p class="text-red-600 font-semibold">${data.mensaje}</p>`;
                }
            })
            .catch(error => console.error("Error:", error));
        }

        function agregarATabla(codigo) {
            let tabla = document.getElementById("tablaSubidos");
            let fila = document.createElement("tr");
            fila.classList.add("border-b", "hover:bg-gray-100");

            let celdaTipo = document.createElement("td");
            celdaTipo.classList.add("px-6", "py-4", "text-black");
            celdaTipo.innerText = "Escaneado";

            let celdaCodigo = document.createElement("td");
            celdaCodigo.classList.add("px-6", "py-4", "text-gray-800");
            celdaCodigo.innerText = codigo;

            let celdaEstado = document.createElement("td");
            celdaEstado.classList.add("px-6", "py-4", "text-gray-600");
            celdaEstado.innerText = "Subido";

            fila.appendChild(celdaTipo);
            fila.appendChild(celdaCodigo);
            fila.appendChild(celdaEstado);
            tabla.appendChild(fila);
        }
    </script>
</x-app-layout>
