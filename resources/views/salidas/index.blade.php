<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Salidas de Camiones') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-black">Registrar Carga en Camión</h1>

        <!-- Input de Escaneo QR -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <label class="block text-black font-semibold">Escanear QR:</label>
            <input type="text" id="qrInput" class="w-full p-2 border rounded mt-2" placeholder="Escanea el QR aquí">
            <button onclick="marcarSubido()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mt-2">
                Confirmar
            </button>
        </div>

        <!-- Tabla de Elementos Subidos -->
        <h1 class="text-2xl font-bold mb-4 text-black">Elementos Subidos al Camión</h1>
        <div class="bg-white shadow-md rounded-lg p-6">
            <table class="w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody id="tablaSubidos" class="text-gray-700 text-sm">
                    <!-- Aquí se insertarán los datos con JavaScript -->
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
                if (data.success) {
                    alert(data.mensaje);
                    agregarATabla(codigo);
                    document.getElementById("qrInput").value = "";
                } else {
                    alert("Error: " + data.mensaje);
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
